<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard?year=2025
     */
    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));

        return response()->json([
            'overall' => $this->overall($year),
            'oo1'     => $this->byOffice('OO1', $year),
            'oo2'     => $this->byOffice('OO2', $year),
            'oo3'     => $this->byOffice('OO3', $year),
            'supply'  => $this->supply(),
            'year'    => $year,
        ]);
    }

    private function overall($year)
    {
        // Total Allocation from ADL Masters (all years)
        $totalAllocation = (float) DB::table('tupad_adl_masters')->sum('total_amount');

        // Total Remaining Budget from ADL Details (all years)
        $totalRemainingBudget = (float) DB::table('tupad_adl_details')->sum('balance');

        // Filtered data for the selected year (from ADL details)
        $query = DB::table('tupad_adl_details')
                    ->whereYear('created_at', $year);

        $allocatedYear = (float) $query->sum('amount');
        $balanceYear   = (float) $query->sum('balance');
        $spentYear     = $allocatedYear - $balanceYear;

        // Base query for beneficiaries (all genders & tags)
        $baseBeneficiaryQuery = DB::table('tupad_bens_status')
            ->join('tupad_adl_breakdowns', 'tupad_bens_status.tupad_adl_breakdown_id', '=', 'tupad_adl_breakdowns.id')
            ->join('tupad_adl_details', 'tupad_adl_breakdowns.adl_detail_id', '=', 'tupad_adl_details.id')
            ->whereYear('tupad_adl_details.created_at', $year);

        // Total real beneficiaries (individual people)
        $totalBeneficiaries = (int) $baseBeneficiaryQuery->count();

        // Female count – case‑insensitive
        $femaleCount = (int) (clone $baseBeneficiaryQuery)
            ->whereRaw("LOWER(TRIM(COALESCE(sex_raw,''))) IN ('female','f')")
            ->count();

        $maleCount = $totalBeneficiaries - $femaleCount;

        // Tag counts – each uses a fresh clone of the base query
        $fourPsCount = (int) (clone $baseBeneficiaryQuery)->where('is_four_ps', 1)->count();
        $pwdCount    = (int) (clone $baseBeneficiaryQuery)->where('is_pwd', 1)->count();
        $seniorCount = (int) (clone $baseBeneficiaryQuery)->where('is_senior', 1)->count();

        // Sum of beneficiaries from breakdowns (for backward compatibility)
        $beneficiariesSum = (int) DB::table('tupad_adl_breakdowns')
            ->join('tupad_adl_details', 'tupad_adl_breakdowns.adl_detail_id', '=', 'tupad_adl_details.id')
            ->whereYear('tupad_adl_details.created_at', $year)
            ->sum('beneficiaries');

        return [
            'total_records'             => $query->count(),
            'total_allocation'          => $totalAllocation,
            'total_remaining_budget'    => $totalRemainingBudget,
            'total_balance'             => $balanceYear,
            'total_spent'               => $spentYear,
            'total_beneficiaries'       => $beneficiariesSum, // kept for existing cards
            'utilization'               => $this->utilization($allocatedYear, $balanceYear),

            // New fields for beneficiary demographics
            'total_beneficiaries_real'  => $totalBeneficiaries,
            'female_beneficiaries'      => $femaleCount,
            'male_beneficiaries'        => $maleCount,
            'four_ps_beneficiaries'     => $fourPsCount,
            'pwd_beneficiaries'         => $pwdCount,
            'senior_beneficiaries'      => $seniorCount,
        ];
    }

    private function byOffice($office, $year)
    {
        $query = DB::table('tupad_adl_details')
                    ->where('adl', 'LIKE', "%{$office}%")
                    ->whereYear('created_at', $year);

        $amount  = (float) $query->sum('amount');
        $balance = (float) $query->sum('balance');

        return [
            'total_records' => $query->count(),
            'total_amount'  => $amount,
            'total_balance' => $balance,
            'utilization'   => $this->utilization($amount, $balance),
        ];
    }

    private function supply()
    {
        return [
            'total_products' => (int) DB::table('tbl_products')->count(),
            'low_stock'      => (int) DB::table('tbl_products')
                                ->where('current_stock', '>', 0)
                                ->where('current_stock', '<=', 10)
                                ->count(),
            'out_of_stock'   => (int) DB::table('tbl_products')
                                ->where('current_stock', '<=', 0)
                                ->count(),
            'total_stock'    => (int) DB::table('tbl_products')->sum('current_stock'),
        ];
    }

    private function utilization($amount, $balance)
    {
        if ($amount <= 0) return 0;
        return round((($amount - $balance) / $amount) * 100, 2);
    }
}