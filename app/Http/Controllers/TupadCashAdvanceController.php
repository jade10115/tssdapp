<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TupadAdlDetail;
use App\Models\TupadCashAdvance;
use App\Models\TupadCashAdvanceItem;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TupadCashAdvanceController extends Controller
{
    // GET all cash advances with totals
    public function index()
    {
        return DB::table('tupad_cash_advances')
            ->leftJoin('tbl_user_profile', 'tbl_user_profile.user_id', '=', 'tupad_cash_advances.employee_id')
            ->leftJoin('tupad_adl_details', 'tupad_adl_details.id', '=', 'tupad_cash_advances.adl_id')
            ->leftJoin('tupad_cash_advance_items', 'tupad_cash_advance_items.cash_advance_id', '=', 'tupad_cash_advances.id')
            ->select(
                'tupad_cash_advances.id',
                'tupad_cash_advances.employee_id',
                'tupad_cash_advances.adl_id',
                'tupad_cash_advances.total_amount',
                DB::raw("CONCAT(tbl_user_profile.first_name,' ',tbl_user_profile.last_name) as employee"),
                'tupad_adl_details.adl',
                DB::raw('SUM(tupad_cash_advance_items.beneficiaries) as total_beneficiaries'),
                DB::raw('COUNT(tupad_cash_advance_items.id) as total_lgu')
            )
            ->groupBy(
                'tupad_cash_advances.id',
                'tbl_user_profile.first_name',
                'tbl_user_profile.last_name',
                'tupad_adl_details.adl',
                'tupad_cash_advances.employee_id',
                'tupad_cash_advances.adl_id',
                'tupad_cash_advances.total_amount'
            )
            ->orderBy('tupad_cash_advances.created_at', 'desc')
            ->get();
    }

    // GET a single cash advance with its items (grouped by ADL)
    public function show($id)
    {
        $cashAdvance = TupadCashAdvance::with('items.breakdown.adlDetail.master')->findOrFail($id);

        $employee = DB::table('tbl_user_profile')
            ->where('user_id', $cashAdvance->employee_id)
            ->first();

        $adl = TupadAdlDetail::find($cashAdvance->adl_id);

        $items = $cashAdvance->items->map(function ($item) {
            $breakdown = $item->breakdown;
            $adlName = 'Unknown';
            if ($breakdown && $breakdown->adlDetail) {
                $detail = $breakdown->adlDetail;
                if ($detail->master) {
                    $adlName = $detail->master->adl . ' - ' . $detail->adl;
                } else {
                    $adlName = $detail->adl;
                }
            }
            return [
                'id' => $item->id,
                'breakdown_id' => $item->adl_breakdown_id,
                'lgu' => $breakdown->lgu ?? $item->lgu ?? 'N/A',
                'beneficiaries' => $item->beneficiaries,
                'amount' => $item->amount,
                'adl' => $adlName,
            ];
        });

        return response()->json([
            'id' => $cashAdvance->id,
            'employee_id' => $cashAdvance->employee_id,
            'employee' => $employee ? $employee->first_name . ' ' . $employee->last_name : 'Unknown',
            'employee_position' => $employee->position ?? '',
            'adl_id' => $cashAdvance->adl_id,
            'adl' => $adl ? $adl->adl : 'Unknown',
            'total_lgu' => $items->count(),
            'total_beneficiaries' => $cashAdvance->total_beneficiaries,
            'total_amount' => $cashAdvance->total_amount,
            'created_at' => $cashAdvance->created_at,
            'items' => $items,
        ]);
    }

    // GET breakdown details for a specific ADL
    public function breakdown(Request $request)
    {
        $adl_id = $request->query('adl_id');
        if (!$adl_id) return response()->json([]);

        $used = DB::table('tupad_cash_advance_items')->pluck('adl_breakdown_id')->toArray();

        $details = TupadAdlDetail::with('breakdowns')->where('adl_master_id', $adl_id)->get();

        $result = [];
        foreach ($details as $detail) {
            foreach ($detail->breakdowns as $b) {
                $isUsed = in_array($b->id, $used);
                $result[] = [
                    'id' => $b->id,
                    'lgu' => $b->lgu,
                    'beneficiaries' => $b->beneficiaries,
                    'amount' => $b->amount,
                    'advanced_by' => $isUsed ? 'Already Used' : null,
                    'disabled' => $isUsed
                ];
            }
        }
        return response()->json($result);
    }

    // Get all available ADLs (list of ADL details)
    public function adls()
    {
        $adls = TupadAdlDetail::all();
        return response()->json($adls);
    }

    // Get all employees
    public function employees()
    {
        $employees = DB::table('tbl_user_profile')
            ->select('tbl_user_profile.user_id', 'tbl_user_profile.first_name', 'tbl_user_profile.last_name', 'tbl_position.position')
            ->leftJoin('tbl_position', 'tbl_position.id', '=', 'tbl_user_profile.position_id')
            ->get();
        return response()->json($employees);
    }

    // Store a new cash advance
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'adl_id' => 'required',
            'item_ids' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $totalBeneficiaries = 0;
            $itemsData = [];

            foreach ($request->item_ids as $breakdownId) {
                if (TupadCashAdvanceItem::where('adl_breakdown_id', $breakdownId)->exists()) continue;

                $breakdown = DB::table('tupad_adl_breakdowns')->where('id', $breakdownId)->first();
                if (!$breakdown) continue;

                $totalAmount += $breakdown->amount;
                $totalBeneficiaries += $breakdown->beneficiaries;

                $itemsData[] = [
                    'adl_breakdown_id' => $breakdownId,
                    'lgu'              => $breakdown->lgu,
                    'amount'           => $breakdown->amount,
                    'beneficiaries'    => $breakdown->beneficiaries,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            $cashAdvance = TupadCashAdvance::create([
                'employee_id' => $request->employee_id,
                'adl_id' => $request->adl_id,
                'total_amount' => $totalAmount,
                'total_beneficiaries' => $totalBeneficiaries
            ]);

            foreach ($itemsData as $itemData) {
                $itemData['cash_advance_id'] = $cashAdvance->id;
                TupadCashAdvanceItem::create($itemData);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update an existing cash advance (replace all items)
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required',
            'adl_id' => 'required',
            'item_ids' => 'required|array'
        ]);

        $cashAdvance = TupadCashAdvance::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete existing items
            TupadCashAdvanceItem::where('cash_advance_id', $id)->delete();

            $totalAmount = 0;
            $totalBeneficiaries = 0;
            $itemsData = [];

            foreach ($request->item_ids as $breakdownId) {
                // Ensure the breakdown is not already used in another cash advance
                $exists = TupadCashAdvanceItem::where('adl_breakdown_id', $breakdownId)
                    ->where('cash_advance_id', '!=', $id)
                    ->exists();
                if ($exists) continue;

                $breakdown = DB::table('tupad_adl_breakdowns')->where('id', $breakdownId)->first();
                if (!$breakdown) continue;

                $totalAmount += $breakdown->amount;
                $totalBeneficiaries += $breakdown->beneficiaries;

                $itemsData[] = [
                    'cash_advance_id' => $id,
                    'adl_breakdown_id' => $breakdownId,
                    'lgu' => $breakdown->lgu,
                    'amount' => $breakdown->amount,
                    'beneficiaries' => $breakdown->beneficiaries,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TupadCashAdvanceItem::insert($itemsData);

            // Update cash advance totals
            $cashAdvance->update([
                'employee_id' => $request->employee_id,
                'adl_id' => $request->adl_id,
                'total_amount' => $totalAmount,
                'total_beneficiaries' => $totalBeneficiaries,
            ]);

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete a cash advance and its associated items
    public function destroy($id)
    {
        $cashAdvance = TupadCashAdvance::findOrFail($id);
        TupadCashAdvanceItem::where('cash_advance_id', $id)->delete();
        $cashAdvance->delete();
        return response()->json(['success' => true]);
    }

    // Export cash advance to DOCX
   /**
 * Export cash advance to Excel using a template.
 */
public function exportExcel($id)
{
    try {
        $cashAdvance = TupadCashAdvance::with('items.breakdown')->findOrFail($id);

        $employee = DB::table('tbl_user_profile')
            ->where('user_id', $cashAdvance->employee_id)
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Get Regional Director
        $regionalDirector = DB::table('tbl_user_profile')
            ->leftJoin('tbl_position', 'tbl_position.id', '=', 'tbl_user_profile.position_id')
            ->where('tbl_position.position', 'Regional Director')
            ->first();

        if (!$regionalDirector) {
            $regionalDirector = (object) [
                'first_name' => 'Regional Director',
                'last_name' => '',
                'position' => 'Regional Director'
            ];
        }

        $totalAmountNum = $cashAdvance->total_amount;
        $totalAmountWords = $this->numberToWords($totalAmountNum);

        // Prepare LGU rows (up to 3 rows, as template has rows 17-19)
        $rows = [];
        foreach ($cashAdvance->items as $index => $item) {
            $lgu = $item->breakdown->lgu ?? $item->lgu ?? '—';
            $rows[] = [
                'lgu' => $lgu,
                'beneficiaries' => number_format($item->beneficiaries),
                'amount' => number_format($item->amount, 2)
            ];
        }
        // Fill only first 3 rows, leave others blank
        for ($i = 0; $i < 3; $i++) {
            if (!isset($rows[$i])) {
                $rows[$i] = ['lgu' => '', 'beneficiaries' => '', 'amount' => ''];
            }
        }

        // Date formatting
        $now = now();
        $day = $now->format('d');
        $daySuffix = $this->getDaySuffix($day);
        $month = $now->format('F');
        $year = $now->format('Y');
        $dayWithSuffix = $day . $daySuffix; // e.g., "23rd"

        // Load the template
        $templatePath = public_path('docs/cashadvance.xlsx');
        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found: ' . $templatePath], 500);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // --- Fill data according to the user's specifications ---

        // 1. Employee name + position (merged I10:L10)
        $employeeFullName = $employee->first_name . ' ' . $employee->last_name;
        $employeePosition = $employee->position ?? '';
        $employeeInfo = $employeeFullName . ($employeePosition ? ' – ' . $employeePosition : '');
        $sheet->setCellValue('I10', $employeeInfo);

        // 2. Total amount in words (merged I11:M11)
        $sheet->setCellValue('I11', $totalAmountWords);

        // 3. Total amount numeric with peso sign (merged N11:P11)
        $sheet->setCellValue('N11', '₱ ' . number_format($totalAmountNum, 2));

        // 4. ADL (merged H15:J15) – use the ADL from cash advance
        $adlName = $cashAdvance->adl ?? '—';
        $sheet->setCellValue('H15', $adlName);

        // 5. LGU data – rows 17,18,19
        // Columns: LGU in GHI (G,H,I merged?), Beneficiaries in JKL (J,K,L merged?), Amount in MNO (M,N,O merged?)
        // We'll assume each set is a merged cell spanning three columns, and we set the value in the first cell.
        $sheet->setCellValue('G17', $rows[0]['lgu']);
        $sheet->setCellValue('G18', $rows[1]['lgu']);
        $sheet->setCellValue('G19', $rows[2]['lgu']);

        $sheet->setCellValue('J17', $rows[0]['beneficiaries']);
        $sheet->setCellValue('J18', $rows[1]['beneficiaries']);
        $sheet->setCellValue('J19', $rows[2]['beneficiaries']);

        $sheet->setCellValue('M17', '₱ ' . $rows[0]['amount']);
        $sheet->setCellValue('M18', '₱ ' . $rows[1]['amount']);
        $sheet->setCellValue('M19', '₱ ' . $rows[2]['amount']);

        // 6. Day and month
        $sheet->setCellValue('F22', $dayWithSuffix);
        $sheet->setCellValue('H22', $month);  // assuming H22:I22 merged

        // 7. Regional Director full name (merged F27:H27)
        $rdFullName = $regionalDirector->first_name . ' ' . $regionalDirector->last_name;
        $sheet->setCellValue('F27', $rdFullName);

        // 8. Regional Director position (merged F28:H28)
        $rdPosition = $regionalDirector->position ?? 'Regional Director';
        $sheet->setCellValue('F28', $rdPosition);

        // Save to temporary file
        $fileName = 'cash_advance_' . $cashAdvance->id . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'CA') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        \Log::error('Excel export error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    // Get all available breakdowns (for edit modal)
    public function allBreakdowns()
    {
        $breakdowns = DB::table('tupad_adl_breakdowns')
            ->join('tupad_adl_details', 'tupad_adl_breakdowns.adl_detail_id', '=', 'tupad_adl_details.id')
            ->join('tupad_adl_masters', 'tupad_adl_details.adl_master_id', '=', 'tupad_adl_masters.id')
            ->select(
                'tupad_adl_breakdowns.id',
                'tupad_adl_breakdowns.lgu',
                'tupad_adl_breakdowns.beneficiaries',
                'tupad_adl_breakdowns.amount',
                DB::raw("CONCAT(tupad_adl_masters.adl, ' - ', tupad_adl_details.adl) as adl")
            )
            ->get();
        return response()->json($breakdowns);
    }

    // ====================== HELPER METHODS ======================
    private function numberToWords($number)
    {
        $number = round($number, 2);
        $whole = floor($number);
        $cents = round(($number - $whole) * 100);

        $words = $this->convertWholeNumberToWords($whole);
        if ($cents > 0) {
            $words .= ' and ' . $this->convertWholeNumberToWords($cents) . ' centavos';
        }
        return ucfirst($words) . ' Pesos';
    }

    private function convertWholeNumberToWords($number)
    {
        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $thousands = ['', 'Thousand', 'Million', 'Billion'];

        if ($number == 0) return 'Zero';

        $num = (string) $number;
        $groups = array_reverse(str_split(str_pad($num, ceil(strlen($num)/3)*3, '0', STR_PAD_LEFT), 3));
        $groupCount = count($groups);
        $words = '';

        for ($i = 0; $i < $groupCount; $i++) {
            $group = (int) $groups[$i];
            if ($group == 0) continue;

            $groupWords = '';
            $hundreds = floor($group / 100);
            $remainder = $group % 100;

            if ($hundreds > 0) {
                $groupWords .= $units[$hundreds] . ' Hundred ';
            }

            if ($remainder >= 10 && $remainder <= 19) {
                $groupWords .= $teens[$remainder - 10] . ' ';
            } else {
                $tensDigit = floor($remainder / 10);
                $onesDigit = $remainder % 10;
                if ($tensDigit > 1) {
                    $groupWords .= $tens[$tensDigit] . ' ';
                }
                if ($onesDigit > 0) {
                    $groupWords .= $units[$onesDigit] . ' ';
                }
            }

            $words = $groupWords . $thousands[$i] . ' ' . $words;
        }

        return trim($words);
    }

    private function getDaySuffix($day)
    {
        if (!in_array(($day % 100), [11,12,13])) {
            switch ($day % 10) {
                case 1: return 'st';
                case 2: return 'nd';
                case 3: return 'rd';
            }
        }
        return 'th';
    }
}