<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DivisionReportExport;

class DivisionController extends Controller
{
    /**
     * ✅ GET /api/divisions?year=YYYY
     *
     * FIX:
     * - total_amount = SUM(tupad_adl_details.amount) per division
     * - total_balance = SUM(tupad_adl_details.balance) per division
     * - year filter uses:
     *      (YEAR(created_at)=year) OR (created_at IS NULL AND adl starts with year)
     * - still returns ALL divisions even if no records
     */
    public function index(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));

        // Always return all divisions
        $divisions = Division::orderBy('division')->get();

        // ✅ Aggregate totals per division for selected year
        $adlAgg = DB::table('tupad_adl_details')
            ->select(
                'division_id',
                DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
                DB::raw('COALESCE(SUM(balance), 0) as total_balance'),
                DB::raw('COUNT(*) as adl_count')
            )
            ->where(function ($q) use ($year) {
                $q->whereYear('created_at', $year)
                  ->orWhere(function ($q2) use ($year) {
                      $q2->whereNull('created_at')
                         ->whereRaw('LEFT(adl, 4) = ?', [(string)$year]);
                  });
            })
            ->groupBy('division_id')
            ->get()
            ->keyBy('division_id');

        $data = $divisions->map(function ($div) use ($adlAgg) {
            $row = $adlAgg->get($div->id, (object) [
                'total_amount' => 0,
                'total_balance' => 0,
                'adl_count' => 0
            ]);

            $totalAmount  = (float) $row->total_amount;
            $totalBalance = (float) $row->total_balance;

            $percentage = 0;
            if ($totalAmount > 0) {
                $percentage = round((($totalAmount - $totalBalance) / $totalAmount) * 100, 2);
                $percentage = max(0, min(100, $percentage));
            }

            return [
                'id'                  => $div->id,
                'division'            => $div->division,
                'total_amount'        => $totalAmount,
                'balance'             => $totalBalance,
                'total_spent'         => max(0, $totalAmount - $totalBalance),
                'percentage'          => $percentage,
                'adl_count'           => (int) $row->adl_count,
                'status'              => $this->status($percentage),

                // keep key for compatibility
                'total_beneficiaries' => 0,
            ];
        });

        return response()->json($data);
    }

    /**
     * ✅ GET /api/divisions/{id}?year=YYYY
     * Same year logic fix as index()
     */
    public function show($id, Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $division = Division::findOrFail($id);

        $row = DB::table('tupad_adl_details')
            ->select(
                DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
                DB::raw('COALESCE(SUM(balance), 0) as total_balance'),
                DB::raw('COUNT(*) as adl_count')
            )
            ->where('division_id', $division->id)
            ->where(function ($q) use ($year) {
                $q->whereYear('created_at', $year)
                  ->orWhere(function ($q2) use ($year) {
                      $q2->whereNull('created_at')
                         ->whereRaw('LEFT(adl, 4) = ?', [(string)$year]);
                  });
            })
            ->first();

        $totalAmount  = (float) ($row->total_amount ?? 0);
        $totalBalance = (float) ($row->total_balance ?? 0);

        $percentage = 0;
        if ($totalAmount > 0) {
            $percentage = round((($totalAmount - $totalBalance) / $totalAmount) * 100, 2);
            $percentage = max(0, min(100, $percentage));
        }

        $division->total_amount = $totalAmount;
        $division->balance = $totalBalance;
        $division->total_spent = max(0, $totalAmount - $totalBalance);
        $division->percentage = $percentage;
        $division->adl_count = (int) ($row->adl_count ?? 0);
        $division->status = $this->status($percentage);

        $division->total_beneficiaries = 0;

        return response()->json($division);
    }

    /**
     * Export divisions data to Excel (kept)
     */
    public function export(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        return Excel::download(new DivisionReportExport($year), "tupad-reports-{$year}.xlsx");
    }

    private function status($percentage)
    {
        if ($percentage >= 90) return "Completed";
        if ($percentage >= 50) return "Active";
        return "Pending";
    }

 
public function list()
{
    return response()->json(
        \App\Models\Division::query()
            ->select('id', 'division', 'color')
            ->orderBy('division')
            ->get()
    );
}


}
