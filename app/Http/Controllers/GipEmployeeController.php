<?php

namespace App\Http\Controllers;

use App\Models\GipEmployee;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GipEmployeeController extends Controller
{
    // ── GET /api/gip/employees?year=YYYY ──────────────────────
    public function index(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));

        $q = GipEmployee::with('office')
            ->whereYear('created_at', $year)
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('family_name', 'like', "%$s%")
                      ->orWhere('first_name', 'like', "%$s%")
                      ->orWhere('middle_name', 'like', "%$s%")
                      ->orWhere('email', 'like', "%$s%")
                      ->orWhere('mobile_no', 'like', "%$s%");
                });
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->office_id, fn ($q, $o) => $q->where('office_id', $o))
            ->orderBy('family_name')
            ->orderBy('first_name');

        return response()->json(
            $q->get()->map(fn ($g) => $this->transform($g))
        );
    }

    // ── GET /api/gip/employees/stats?year=YYYY ────────────────
    public function stats(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $all = GipEmployee::whereYear('created_at', $year)->get();

        return response()->json([
            'total'    => $all->count(),
            'active'   => $all->where('status', 'Active')->count(),
            'female'   => $all->where('gender', 'Female')->count(),
            'male'     => $all->where('gender', 'Male')->count(),
            'pwd'      => $all->where('is_pwd', true)->count(),
            '4ps'      => $all->where('is_4ps_beneficiary', true)->count(),
            'completed' => $all->where('status', 'Completed')->count(),
            'terminated' => $all->where('status', 'Terminated')->count(),
        ]);
    }

    // ── GET /api/gip/employees/years ──────────────────────────
    public function availableYears()
    {
        $years = GipEmployee::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->toArray();

        $currentYear = Carbon::now()->year;
        $nextYear    = $currentYear + 1;

        if (!in_array($currentYear, $years)) $years[] = $currentYear;
        if (!in_array($nextYear, $years))    $years[] = $nextYear;

        sort($years);
        return response()->json($years);
    }

    // ── GET /api/gip/offices ──────────────────────────────────
    public function offices()
    {
        return response()->json(Office::orderBy('name')->get(['id', 'name']));
    }

    // ── POST /api/gip/employees/check-duplicate ──────────────
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'family_name' => 'required|string',
            'first_name'  => 'required|string',
            'middle_name' => 'nullable|string',
            'date_of_birth' => 'required|date',
        ]);

        $query = GipEmployee::where('family_name', $validated['family_name'])
            ->where('first_name', $validated['first_name'])
            ->whereDate('date_of_birth', $validated['date_of_birth']);

        if (!empty($validated['middle_name'])) {
            $query->where('middle_name', $validated['middle_name']);
        }

        $existing = $query->get();

        if ($existing->isEmpty()) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'records' => $existing->map(fn ($g) => $this->transform($g)),
        ]);
    }

    // ── POST /api/gip/employees ───────────────────────────────
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')
                ->store('gip/photos', 'public');
        }

        $validated['educational_attainment'] = $this->decodeJson($request->input('educational_attainment'));
        $validated['work_experience']         = $this->decodeJson($request->input('work_experience'));

        $validated['original_start_date'] = $validated['contract_start_date'];
        $validated['status']              = $validated['status'] ?? 'Active';

        $gip = GipEmployee::create($validated);

        return response()->json(
            $this->transform($gip->load('office')),
            201
        );
    }

    // ── GET /api/gip/employees/{id} ───────────────────────────
    public function show($id)
    {
        $gip = GipEmployee::with('office')->findOrFail($id);
        return response()->json($this->transform($gip));
    }

    // ── PUT /api/gip/employees/{id} ───────────────────────────
    public function update(Request $request, $id)
    {
        $gip       = GipEmployee::findOrFail($id);
        $validated = $this->validatePayload($request, isUpdate: true);

        if ($request->hasFile('photo')) {
            if ($gip->photo_path) {
                Storage::disk('public')->delete($gip->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')
                ->store('gip/photos', 'public');
        }

        $validated['educational_attainment'] = $this->decodeJson($request->input('educational_attainment'));
        $validated['work_experience']         = $this->decodeJson($request->input('work_experience'));

        $gip->update($validated);

        return response()->json($this->transform($gip->fresh('office')));
    }

    // ── DELETE /api/gip/employees/{id} ───────────────────────
    public function destroy($id)
    {
        $gip = GipEmployee::findOrFail($id);

        if ($gip->photo_path) {
            Storage::disk('public')->delete($gip->photo_path);
        }

        $gip->delete();

        return response()->json(['message' => 'GIP employee deleted.']);
    }

    // ── POST /api/gip/employees/{id}/renew ───────────────────
    public function renew(Request $request, $id)
    {
        $gip = GipEmployee::findOrFail($id);

        $request->validate([
            'new_end_date' => [
                'required',
                'date',
                'after:' . $gip->contract_end_date->format('Y-m-d'),
            ],
        ]);

        $originalStart = $gip->original_start_date ?? $gip->contract_start_date;
        $totalMonths   = Carbon::parse($originalStart)
            ->diffInMonths($request->new_end_date);

        if ($totalMonths > 12) {
            return response()->json([
                'error' => 'Total contract length cannot exceed 12 months from the original start date.',
            ], 422);
        }

        $gip->update([
            'previous_end_date'   => $gip->contract_end_date->format('Y-m-d'),
            'contract_end_date'   => $request->new_end_date,
            'original_start_date' => $originalStart->format('Y-m-d'),
            'renewal_count'       => $gip->renewal_count + 1,
        ]);

        return response()->json($this->transform($gip->fresh('office')));
    }

    // ── POST /api/gip/employees/{id}/resign ──────────────────
    public function resign(Request $request, $id)
    {
        $gip = GipEmployee::findOrFail($id);

        $validated = $request->validate([
            'resignation_date' => 'required|date|before_or_equal:today',
        ]);

        $gip->update([
            'status' => 'Resigned',
            'contract_end_date' => $validated['resignation_date'], // optionally set end date
        ]);

        return response()->json($this->transform($gip->fresh('office')));
    }

    // ── Helpers ───────────────────────────────────────────────

    protected function validatePayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'family_name'                => 'required|string|max:191',
            'first_name'                 => 'required|string|max:191',
            'middle_name'                => 'nullable|string|max:191',
            'residential_address'        => 'nullable|string',
            'telephone_no'               => 'nullable|string|max:60',
            'mobile_no'                  => 'nullable|string|max:60',
            'email'                      => 'nullable|email|max:191',
            'place_of_birth'             => 'nullable|string|max:191',
            'date_of_birth'              => 'nullable|date',
            'gender'                     => 'required|in:Male,Female',
            'civil_status'               => 'required|in:Single,Married,Widow/Widower',

            'educational_attainment'     => 'nullable',
            'work_experience'            => 'nullable',

            'is_pwd'                     => 'nullable|boolean',
            'is_ip'                      => 'nullable|boolean',
            'is_disaster_victim'         => 'nullable|boolean',
            'is_armed_conflict_victim'   => 'nullable|boolean',
            'is_rebel_returnee'          => 'nullable|boolean',
            'is_4ps_beneficiary'         => 'nullable|boolean',
            'other_vulnerable_group'     => 'nullable|string|max:191',

            'office_id'                  => 'nullable|exists:offices,id',
            'contract_start_date'        => 'required|date',
            'contract_end_date'          => 'required|date|after:contract_start_date',
            'status'                     => 'nullable|in:Active,Completed,Terminated',

            'emergency_contact_name'     => 'nullable|string|max:191',
            'emergency_contact_details'  => 'nullable|string|max:191',
            'emergency_contact_address'  => 'nullable|string',

            'gsis_beneficiary_name'         => 'nullable|string|max:191',
            'gsis_beneficiary_relationship' => 'nullable|string|max:100',

            'interviewed_by'     => 'nullable|string|max:191',
            'date_accomplished'  => 'nullable|date',
            'doc_birth_cert'     => 'nullable|boolean',
            'doc_transcript'     => 'nullable|boolean',
            'doc_barangay_cert'  => 'nullable|boolean',
            'doc_form_137'       => 'nullable|boolean',
            'doc_diploma'        => 'nullable|boolean',
            'doc_school_cert'    => 'nullable|boolean',
            'doc_other'          => 'nullable|string|max:191',
            'psoc_code'          => 'nullable|string|max:100',

            'photo' => 'nullable|image|max:2048',
        ]);
    }

    protected function decodeJson($value): ?array
    {
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    protected function transform(GipEmployee $g): array
    {
        $originalStart = $g->original_start_date ?? $g->contract_start_date;
        $totalUsed     = Carbon::parse($originalStart)->diffInMonths($g->contract_end_date);

        return [
            'id'                           => $g->id,
            'family_name'                  => $g->family_name,
            'first_name'                   => $g->first_name,
            'middle_name'                  => $g->middle_name,
            'full_name'                    => implode(', ', array_filter([
                $g->family_name,
                trim($g->first_name . ' ' . ($g->middle_name ?? '')),
            ])),
            'residential_address'          => $g->residential_address,
            'telephone_no'                 => $g->telephone_no,
            'mobile_no'                    => $g->mobile_no,
            'email'                        => $g->email,
            'place_of_birth'               => $g->place_of_birth,
            'date_of_birth'                => $g->date_of_birth?->format('Y-m-d'),
            'gender'                       => $g->gender,
            'civil_status'                 => $g->civil_status,
            'educational_attainment'       => $g->educational_attainment ?? [],
            'work_experience'              => $g->work_experience ?? [],
            'is_pwd'                       => (bool) $g->is_pwd,
            'is_ip'                        => (bool) $g->is_ip,
            'is_disaster_victim'           => (bool) $g->is_disaster_victim,
            'is_armed_conflict_victim'     => (bool) $g->is_armed_conflict_victim,
            'is_rebel_returnee'            => (bool) $g->is_rebel_returnee,
            'is_4ps_beneficiary'           => (bool) $g->is_4ps_beneficiary,
            'other_vulnerable_group'       => $g->other_vulnerable_group,
            'office_id'                    => $g->office_id,
            'office'                       => $g->office ? ['id' => $g->office->id, 'name' => $g->office->name] : null,
            'contract_start_date'          => $g->contract_start_date?->format('Y-m-d'),
            'contract_end_date'            => $g->contract_end_date?->format('Y-m-d'),
            'original_start_date'          => $g->original_start_date?->format('Y-m-d'),
            'previous_end_date'            => $g->previous_end_date?->format('Y-m-d'),
            'renewal_count'                => $g->renewal_count,
            'remaining_months'             => max(0, 12 - $totalUsed),   // integer
            'total_months_used'            => (int) floor($totalUsed),
            'status'                       => $g->status,
            'emergency_contact_name'       => $g->emergency_contact_name,
            'emergency_contact_details'    => $g->emergency_contact_details,
            'emergency_contact_address'    => $g->emergency_contact_address,
            'gsis_beneficiary_name'        => $g->gsis_beneficiary_name,
            'gsis_beneficiary_relationship' => $g->gsis_beneficiary_relationship,
            'interviewed_by'               => $g->interviewed_by,
            'date_accomplished'            => $g->date_accomplished?->format('Y-m-d'),
            'doc_birth_cert'               => (bool) $g->doc_birth_cert,
            'doc_transcript'               => (bool) $g->doc_transcript,
            'doc_barangay_cert'            => (bool) $g->doc_barangay_cert,
            'doc_form_137'                 => (bool) $g->doc_form_137,
            'doc_diploma'                  => (bool) $g->doc_diploma,
            'doc_school_cert'              => (bool) $g->doc_school_cert,
            'doc_other'                    => $g->doc_other,
            'psoc_code'                    => $g->psoc_code,
            'photo_url'                    => $g->photo_path ? asset('storage/' . $g->photo_path) : null,
            'created_at'                   => $g->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}