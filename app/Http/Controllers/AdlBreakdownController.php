<?php

namespace App\Http\Controllers;

use App\Models\TupadAdlBreakdown;
use App\Models\TupadAdlDetail;
use App\Models\TupadBenStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;     
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdlBreakdownController extends Controller
{
    public function index(Request $request)
    {
        $adlDetailId = $request->query('adl_detail_id');

        $q = TupadAdlBreakdown::query();

        if ($adlDetailId) {
            $q->where('adl_detail_id', (int) $adlDetailId);
        }

        return response()->json($q->orderByDesc('id')->get());
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'adl_detail_id' => ['required', 'integer'],
            'lgu' => ['required', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,received'],
            'osh_date' => ['nullable', 'date'],
            'payout_date' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $row = TupadAdlBreakdown::create([
            'adl_detail_id' => (int) $request->adl_detail_id,
            'lgu' => trim($request->lgu),
            'beneficiaries' => (int) ($request->beneficiaries ?? 0),
            'amount' => (float) ($request->amount ?? 0),
            'status' => $request->status ?? 'pending',
            'osh_date' => $request->osh_date,
            'payout_date' => $request->payout_date,
            'four_ps' => 0,
            'seniors' => 0,
            'pwd' => 0,
            'female' => 0,
        ]);

        return response()->json(['message' => 'Created', 'row' => $row], 201);
    }

    public function update(Request $request, $id)
    {
        $row = TupadAdlBreakdown::findOrFail($id);

        $v = Validator::make($request->all(), [
            'lgu' => ['required', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,received'],
            'osh_date' => ['nullable', 'date'],
            'payout_date' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $row->update([
            'lgu' => trim($request->lgu),
            'beneficiaries' => (int) ($request->beneficiaries ?? 0),
            'amount' => (float) ($request->amount ?? 0),
            'status' => $request->status ?? 'pending',
            'osh_date' => $request->osh_date,
            'payout_date' => $request->payout_date,
        ]);

        $this->recomputeAdlTotals($row->adl_detail_id);

        return response()->json(['message' => 'Updated', 'row' => $row]);
    }

    public function destroy($id)
    {
        $row = TupadAdlBreakdown::findOrFail($id);
        $adlDetailId = $row->adl_detail_id;
        $row->delete();

        $this->recomputeAdlTotals($adlDetailId);

        return response()->json(['message' => 'Deleted']);
    }

    public function markAsReceived($id)
    {
        $row = TupadAdlBreakdown::findOrFail($id);

        if ($row->status === 'received') {
            return response()->json(['message' => 'Already marked as received', 'row' => $row]);
        }

        $row->update(['status' => 'received']);

        $this->recomputeAdlTotals($row->adl_detail_id);

        return response()->json([
            'message' => 'Marked as received & ADL updated',
            'row' => $row,
            'adl' => TupadAdlDetail::find($row->adl_detail_id),
        ]);
    }

    // Import demographics – uses exact column mapping:
    // D(3)=First, E(4)=Middle, F(5)=Last, G(6)=Ext, R(17)=PWD, S(18)=4Ps, U(20)=Sex, W(22)=Age
 public function importDemographics(Request $request, $id)
    {
        // Allow large memory for big Excel files
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes

        $breakdown = TupadAdlBreakdown::findOrFail($id);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $sheet = $worksheet->toArray(null, true, true, false);

        if (count($sheet) <= 1) {
            return response()->json(['message' => 'Excel is empty or has no data rows.'], 422);
        }

        // Security header check (unchanged)
        $headerRow = $sheet[0] ?? [];
        $unwantedKeywords = [
            'First Name', 'Middle Name', 'Last Name', 'Extension Name',
            'Birthdate', 'Project Location', 'Type of ID', 'ID Number',
            'Contact No.', 'E-payment/Account No.', 'Type of Beneficiary',
            'PWD (Yes/No)', '4P\'s Beneficiary (Yes/No)', 'Occupation',
            'Sex', 'Civil Status', 'Age', 'Dependent',
            'Interested in wage employment or self-employment?',
            'Skills Training Needed',
            'Name of Beneficiary',
            'First Name Middle Name Last Name Extension Name'
        ];
        $found = false;
        foreach ($unwantedKeywords as $keyword) {
            foreach ($headerRow as $cell) {
                if (stripos((string)$cell, $keyword) !== false) {
                    $found = true;
                    break 2;
                }
            }
        }
        if ($found) {
            return response()->json([
                'message' => 'Invalid file format: The uploaded file contains the wrong header structure. Please use the correct beneficiary import template.'
            ], 422);
        }

        // Column indexes
        $IDX_FIRST   = 3;
        $IDX_MIDDLE  = 4;
        $IDX_LAST    = 5;
        $IDX_EXT     = 6;
        $IDX_PWD     = 17;
        $IDX_4PS     = 18;
        $IDX_SEX     = 20;
        $IDX_AGE     = 22;

        DB::beginTransaction();

        try {
            TupadBenStatus::where('tupad_adl_breakdown_id', $breakdown->id)->delete();

            $femaleCount = 0;
            $pwdCount = 0;
            $fourPsCount = 0;
            $seniorCount = 0;
            $saved = 0;
            $seen = [];

            $insertData = [];

            foreach ($sheet as $i => $r) {
                if ($i === 0) continue;
                if (!is_array($r)) continue;

                $first = trim((string) ($r[$IDX_FIRST] ?? ''));
                $middle = trim((string) ($r[$IDX_MIDDLE] ?? ''));
                $last = trim((string) ($r[$IDX_LAST] ?? ''));
                $ext = trim((string) ($r[$IDX_EXT] ?? ''));

                if ($first === '' && $last === '') continue;

                $full = trim(implode(' ', array_filter([$first, $middle, $last, $ext], fn($x) => trim((string)$x) !== '')));

                $key = strtolower($full);
                if ($key === '' || isset($seen[$key])) continue;
                $seen[$key] = true;

                $sexRaw = trim((string) ($r[$IDX_SEX] ?? ''));
                $sexNorm = strtolower($sexRaw);
                $isFemale = ($sexNorm === 'female' || $sexNorm === 'f');

                $pwdRaw = trim((string) ($r[$IDX_PWD] ?? ''));
                $fourPsRaw = trim((string) ($r[$IDX_4PS] ?? ''));
                $ageVal = $r[$IDX_AGE] ?? null;
                $age = is_numeric($ageVal) ? (int) $ageVal : (int) preg_replace('/[^0-9]/', '', (string) $ageVal);
                if ($age < 0) $age = 0;

                $isPwd = strtolower($pwdRaw) === 'yes';
                $isFourPs = strtolower($fourPsRaw) === 'yes';
                $isSenior = $age >= 60;

                // Females always saved; males only if they have at least one tag
                if (!$isFemale && !$isPwd && !$isFourPs && !$isSenior) {
                    continue;
                }

                if ($isFemale) $femaleCount++;
                if ($isPwd) $pwdCount++;
                if ($isFourPs) $fourPsCount++;
                if ($isSenior) $seniorCount++;

                $insertData[] = [
                    'tupad_adl_breakdown_id' => $breakdown->id,
                    'first_name' => $first,
                    'middle_name' => $middle ?: null,
                    'last_name' => $last,
                    'ext_name' => $ext ?: null,
                    'full_name' => $full,
                    'is_pwd' => $isPwd ? 1 : 0,
                    'is_four_ps' => $isFourPs ? 1 : 0,
                    'is_senior' => $isSenior ? 1 : 0,
                    'age' => $age ?: null,
                    'sex_raw' => $sexRaw ?: ($isFemale ? 'Female' : 'Male'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $saved++;
            }

            if (!empty($insertData)) {
                // Optional: chunk insert for extremely large sets (>10k)
                $chunkSize = 2000;
                foreach (array_chunk($insertData, $chunkSize) as $chunk) {
                    TupadBenStatus::insert($chunk);
                }
            }

            $breakdown->update([
                'female' => $femaleCount,
                'pwd' => $pwdCount,
                'four_ps' => $fourPsCount,
                'seniors' => $seniorCount,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Imported',
                'counts' => [
                    'female' => $femaleCount,
                    'pwd' => $pwdCount,
                    'four_ps' => $fourPsCount,
                    'seniors' => $seniorCount,
                    'saved' => $saved,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }



    public function beneficiaries(Request $request, $id)
    {
        $breakdown = TupadAdlBreakdown::findOrFail($id);

        $femaleOnly = (int) $request->query('female_only', 1);

        $query = TupadBenStatus::where('tupad_adl_breakdown_id', $breakdown->id);

        $totalAll = (clone $query)->count();

        if ($femaleOnly === 1) {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(TRIM(COALESCE(sex_raw,''))) IN ('female','f')")
                  ->orWhere(function ($sub) {
                      $sub->where('is_pwd', 1)
                          ->orWhere('is_four_ps', 1)
                          ->orWhere('is_senior', 1);
                  });
            });
        }

        $rows = $query->orderBy('full_name')->get();

        $totalFemale = $rows->filter(function ($r) {
            $sex = strtolower(trim($r->sex_raw ?? ''));
            return $sex === 'female' || $sex === 'f';
        })->count();

        return response()->json([
            'breakdown_id' => $breakdown->id,
            'total_all' => $totalAll,
            'total_female' => $totalFemale,
            'rows' => $rows,
        ]);
    }

    public function updateBeneficiaryStatus(Request $request, $id)
    {
        $ben = TupadBenStatus::findOrFail($id);

        $v = Validator::make($request->all(), [
            'is_pwd' => ['nullable', 'boolean'],
            'is_four_ps' => ['nullable', 'boolean'],
            'is_senior' => ['nullable', 'boolean'],
            'age' => ['nullable', 'integer', 'min:0'],
            'sex_raw' => ['nullable', 'string', 'max:50'],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $ben->fill($request->only(['is_pwd', 'is_four_ps', 'is_senior', 'age', 'sex_raw']));
        $ben->save();

        $this->recomputeBreakdownCounts($ben->tupad_adl_breakdown_id);

        return response()->json(['message' => 'Updated', 'row' => $ben]);
    }

    private function recomputeAdlTotals($adlDetailId)
    {
        $adl = TupadAdlDetail::findOrFail($adlDetailId);

        $totalReceived = TupadAdlBreakdown::where('adl_detail_id', $adl->id)
            ->where('status', 'received')
            ->sum('amount');

        $allocated = (float) $adl->amount;
        $balance = max(0, $allocated - $totalReceived);

        $percentage = 0;
        if ($allocated > 0) {
            $percentage = ($totalReceived / $allocated) * 100;
        }
        if ($balance == 0) $percentage = 100;

        $totalLgu = TupadAdlBreakdown::where('adl_detail_id', $adl->id)
            ->where('status', 'received')
            ->count();

        $adl->update([
            'balance' => $balance,
            'percentage' => round($percentage, 2),
            'total_lgu' => $totalLgu,
        ]);
    }

    private function recomputeBreakdownCounts($breakdownId)
    {
        $breakdown = TupadAdlBreakdown::findOrFail($breakdownId);

        $femaleCount = TupadBenStatus::where('tupad_adl_breakdown_id', $breakdownId)
            ->whereRaw("LOWER(TRIM(COALESCE(sex_raw,''))) IN ('female','f')")
            ->count();

        $pwdCount = TupadBenStatus::where('tupad_adl_breakdown_id', $breakdownId)
            ->where('is_pwd', 1)
            ->count();

        $fourPsCount = TupadBenStatus::where('tupad_adl_breakdown_id', $breakdownId)
            ->where('is_four_ps', 1)
            ->count();

        $seniorCount = TupadBenStatus::where('tupad_adl_breakdown_id', $breakdownId)
            ->where('is_senior', 1)
            ->count();

        $breakdown->update([
            'female' => $femaleCount,
            'pwd' => $pwdCount,
            'four_ps' => $fourPsCount,
            'seniors' => $seniorCount,
        ]);
    }
}