<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TupadAdlBreakdown;
use App\Models\TupadAdlDetail;
use App\Models\TupadAdlMaster;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerAdlController extends Controller
{
    /**
     * GET /api/per-adl-breakdown?adl_id=1
     * GET /api/per-adl-breakdown?adl_detail_id=3
     */
    public function breakdown(Request $request)
    {
        $adlId = (int) $request->query('adl_id', 0);                 // master id
        $adlDetailId = (int) $request->query('adl_detail_id', 0);    // detail id

        if ($adlId <= 0 && $adlDetailId <= 0) {
            return response()->json(['message' => 'Provide adl_id OR adl_detail_id.'], 422);
        }

        // ✅ IMPORTANT: Priority rule
        // If adl_detail_id exists, ignore adl_id
        if ($adlDetailId > 0) {
            $adlId = 0;
        }

        $meta = [
            'mode' => null,
            'adl_id' => null,
            'adl_detail_id' => null,
            'adl' => null,
            'sponsor' => null,
            'allocated' => 0,
            'balance' => 0,
        ];

        // ✅ Base query
        $q = TupadAdlBreakdown::query()
            ->from('tupad_adl_breakdowns as b')
            ->join('tupad_adl_details as d', 'd.id', '=', 'b.adl_detail_id')
            ->leftJoin('divisions as dv', 'dv.id', '=', 'd.division_id')
            ->select([
                'b.id',
                'b.adl_detail_id',
                'b.lgu',
                'b.beneficiaries',
                'b.amount',
                'b.osh_date',
                'b.payout_date',
                DB::raw('d.division_id as division_id'),
                DB::raw('COALESCE(dv.division, "") as division_name'),
                DB::raw('d.adl_master_id as adl_master_id'),
            ])
            ->orderByDesc('b.id');

        // ✅ Mode: DETAIL
        if ($adlDetailId > 0) {
            $detail = TupadAdlDetail::with(['master:id,adl,sponsor,total_amount,balance'])->find($adlDetailId);
            if (!$detail) return response()->json(['message' => 'ADL Detail not found.'], 404);

            $q->where('b.adl_detail_id', $adlDetailId);

            $meta['mode'] = 'detail';
            $meta['adl_detail_id'] = $adlDetailId;
            $meta['adl_id'] = (int) ($detail->adl_master_id ?? 0);
            $meta['adl'] = $detail->adl ?? ($detail->master->adl ?? null);
            $meta['sponsor'] = $detail->sponsor ?? ($detail->master->sponsor ?? null);
            $meta['allocated'] = (float) ($detail->amount ?? 0);
            $meta['balance'] = (float) ($detail->balance ?? 0);
        }

        // ✅ Mode: MASTER
        if ($adlId > 0) {
            $master = TupadAdlMaster::find($adlId);
            if (!$master) return response()->json(['message' => 'ADL Master not found.'], 404);

            $q->where('d.adl_master_id', $adlId);

            $meta['mode'] = 'master';
            $meta['adl_id'] = $adlId;
            $meta['adl'] = $master->adl ?? null;
            $meta['sponsor'] = $master->sponsor ?? null;
            $meta['allocated'] = (float) ($master->total_amount ?? 0);
            $meta['balance'] = (float) ($master->balance ?? 0);
        }

        $rows = $q->get()->map(function ($r) {
            $divisionId = $r->division_id !== null ? (int)$r->division_id : null;

            // ✅ division_key fix:
            // "" is used by Vue for "All", so we MUST NOT use "" for No Division.
            $divisionKey = $divisionId === null ? '__NONE__' : (string)$divisionId;

            return [
                'id' => (int) $r->id,
                'adl_detail_id' => (int) $r->adl_detail_id,
                'adl_master_id' => (int) ($r->adl_master_id ?? 0),
                'lgu' => $r->lgu,
                'beneficiaries' => (int) ($r->beneficiaries ?? 0),
                'amount' => (float) ($r->amount ?? 0),
                'osh_date' => $r->osh_date ? substr((string)$r->osh_date, 0, 10) : null,
                'payout_date' => $r->payout_date ? substr((string)$r->payout_date, 0, 10) : null,
                'division_id' => $divisionId,
                'division_name' => (string) ($r->division_name ?: ($divisionId ? "Division {$divisionId}" : "No Division")),
                'division_key' => $divisionKey,
            ];
        });

        // ✅ Totals
        $overall = [
            'rows' => $rows->count(),
            'total_amount' => 0,
            'total_beneficiaries' => 0,
        ];

        $byDivision = [];

        foreach ($rows as $row) {
            $overall['total_amount'] += (float) $row['amount'];
            $overall['total_beneficiaries'] += (int) $row['beneficiaries'];

            $key = $row['division_key'];

            if (!isset($byDivision[$key])) {
                $byDivision[$key] = [
                    'division_key' => $key,
                    'division_id' => $row['division_id'],
                    'division_name' => $row['division_name'],
                    'rows' => 0,
                    'total_amount' => 0,
                    'total_beneficiaries' => 0,
                ];
            }

            $byDivision[$key]['rows'] += 1;
            $byDivision[$key]['total_amount'] += (float) $row['amount'];
            $byDivision[$key]['total_beneficiaries'] += (int) $row['beneficiaries'];
        }

        $totalsByDivision = array_values($byDivision);
        usort($totalsByDivision, fn($a, $b) => strcmp($a['division_name'], $b['division_name']));

        return response()->json([
            'meta' => $meta,
            'rows' => $rows,
            'totals' => $overall,
            'totals_by_division' => $totalsByDivision,
        ]);
    }

    /* ============================================================
       ✅ EXCEL EXPORTS (PhpSpreadsheet)
       Endpoints used by your Vue buttons:
       /api/tupad/reports/workbook?year=2026
       /api/tupad/reports/per-adl?year=2026
       /api/tupad/reports/lgu-per-adl?year=2026
       /api/tupad/reports/all-adl?year=2026
    ============================================================ */

    public function workbook(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->sheetPerAdl($spreadsheet, $year);
        $this->sheetLguPerAdl($spreadsheet, $year);
        $this->sheetAllAdlSummary($spreadsheet, $year);

        return $this->download($spreadsheet, "TUPAD_Report_{$year}_Workbook.xlsx");
    }

    public function perAdl(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->sheetPerAdl($spreadsheet, $year);

        return $this->download($spreadsheet, "TUPAD_Report_{$year}_TAB1_Per_ADL.xlsx");
    }

    public function lguPerAdl(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->sheetLguPerAdl($spreadsheet, $year);

        return $this->download($spreadsheet, "TUPAD_Report_{$year}_TAB2_LGU_Per_ADL.xlsx");
    }

    public function allAdl(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->sheetAllAdlSummary($spreadsheet, $year);

        return $this->download($spreadsheet, "TUPAD_Report_{$year}_TAB3_All_ADL_Summary.xlsx");
    }

    /* =========================
       SHEETS
    ========================== */

    private function sheetPerAdl(Spreadsheet $spreadsheet, int $year)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Per ADL');

        $headers = ['ADL','Sponsor','Total Beneficiaries','Total Amount','Balance','Utilization %','Status'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, count($headers));

        $rows = DB::table('tupad_adl_masters as m')
            ->leftJoin('tupad_adl_details as d', 'd.adl_master_id', '=', 'm.id')
            ->leftJoin('tupad_adl_breakdowns as b', 'b.adl_detail_id', '=', 'd.id')
            ->whereYear('b.osh_date', $year)
            ->selectRaw('
                m.adl,
                m.sponsor,
                SUM(COALESCE(b.beneficiaries,0)) as beneficiaries,
                SUM(COALESCE(b.amount,0)) as amount,
                m.balance,
                m.status
            ')
            ->groupBy('m.id','m.adl','m.sponsor','m.balance','m.status')
            ->get();

        $r = 2;
        foreach ($rows as $row) {
            $amount = (float) ($row->amount ?? 0);
            $balance = (float) ($row->balance ?? 0);
            $util = $amount > 0 ? round((($amount - $balance) / $amount) * 100, 2) : 0;

            $sheet->fromArray([
                $row->adl,
                $row->sponsor,
                (int) $row->beneficiaries,
                $amount,
                $balance,
                "{$util}%",
                $row->status
            ], null, "A{$r}");
            $r++;
        }

        $sheet->getStyle("C2:E{$r}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $this->autoSize($sheet, count($headers));
    }

    private function sheetLguPerAdl(Spreadsheet $spreadsheet, int $year)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('LGU per ADL');

        $headers = ['ADL','LGU','Division','Beneficiaries','Amount','OSH Date','Payout Date','Status'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, count($headers));

        $rows = DB::table('tupad_adl_breakdowns as b')
            ->join('tupad_adl_details as d', 'd.id', '=', 'b.adl_detail_id')
            ->join('tupad_adl_masters as m', 'm.id', '=', 'd.adl_master_id')
            ->leftJoin('divisions as dv', 'dv.id', '=', 'd.division_id')
            ->whereYear('b.osh_date', $year)
            ->select([
                'm.adl',
                'b.lgu',
                'dv.division',
                'b.beneficiaries',
                'b.amount',
                'b.osh_date',
                'b.payout_date',
                'b.status',
                'dv.color'
            ])
            ->orderBy('m.adl')
            ->orderBy('b.lgu')
            ->get();

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row->adl,
                $row->lgu,
                $row->division,
                (int) ($row->beneficiaries ?? 0),
                (float) ($row->amount ?? 0),
                $row->osh_date ? substr((string)$row->osh_date, 0, 10) : null,
                $row->payout_date ? substr((string)$row->payout_date, 0, 10) : null,
                $row->status
            ], null, "A{$r}");

            // Color the Division cell (C column) using divisions.color (#RRGGBB)
            if (!empty($row->color)) {
                $hex = strtoupper(ltrim((string)$row->color, '#'));
                if (strlen($hex) === 6) {
                    $sheet->getStyle("C{$r}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB("FF{$hex}");
                }
            }

            $r++;
        }

        $this->autoSize($sheet, count($headers));
    }

    private function sheetAllAdlSummary(Spreadsheet $spreadsheet, int $year)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('All ADL Summary');

        $headers = ['ADL','Total Beneficiaries','Total Amount','Balance','Utilization %'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeader($sheet, count($headers));

        $rows = DB::table('tupad_adl_masters as m')
            ->leftJoin('tupad_adl_details as d', 'd.adl_master_id', '=', 'm.id')
            ->leftJoin('tupad_adl_breakdowns as b', 'b.adl_detail_id', '=', 'd.id')
            ->whereYear('b.osh_date', $year)
            ->selectRaw('
                m.adl,
                SUM(COALESCE(b.beneficiaries,0)) as beneficiaries,
                SUM(COALESCE(b.amount,0)) as amount,
                m.balance
            ')
            ->groupBy('m.id','m.adl','m.balance')
            ->get();

        $r = 2;
        foreach ($rows as $row) {
            $amount = (float) ($row->amount ?? 0);
            $balance = (float) ($row->balance ?? 0);
            $util = $amount > 0 ? round((($amount - $balance) / $amount) * 100, 2) : 0;

            $sheet->fromArray([
                $row->adl,
                (int) $row->beneficiaries,
                $amount,
                $balance,
                "{$util}%"
            ], null, "A{$r}");
            $r++;
        }

        $this->autoSize($sheet, count($headers));
    }

    /* =========================
       HELPERS
    ========================== */

    private function styleHeader($sheet, int $count)
    {
        $end = chr(64 + $count);

        $sheet->getStyle("A1:{$end}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);
    }

    private function autoSize($sheet, int $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

   private function download(Spreadsheet $spreadsheet, string $filename)
{
    // ✅ IMPORTANT: prevent corrupted Excel
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $writer = new Xlsx($spreadsheet);

    return response()->stream(
        function () use ($writer) {
            $writer->save('php://output');
        },
        200,
        [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'public',
        ]
    );
}

}
