<?php

namespace App\Http\Controllers;

use App\Models\GipOffice;          // ✅ your renamed model
use App\Models\GipEmployee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GipOfficeController extends Controller
{
    // List all offices with yearly GIP count, male/female breakdown
    public function index(Request $request)
    {
        $year = (int) $request->query('year', Carbon::now()->year);

        $offices = GipOffice::withCount([
            'gipEmployees' => function ($q) use ($year) {
                $q->whereYear('created_at', $year);
            },
            'gipEmployees as male_count' => function ($q) use ($year) {
                $q->whereYear('created_at', $year)->where('gender', 'Male');
            },
            'gipEmployees as female_count' => function ($q) use ($year) {
                $q->whereYear('created_at', $year)->where('gender', 'Female');
            }
        ])->orderBy('name')->get();

        $totalOffices = GipOffice::count();

        return response()->json([
            'offices'      => $offices,
            'totalOffices' => $totalOffices,
        ]);
    }

    public function stats(Request $request)
    {
        $year = (int) $request->query('year', Carbon::now()->year);
        $gips = GipEmployee::whereYear('created_at', $year);

        return response()->json([
            'total'  => $gips->count(),
            'male'   => $gips->where('gender', 'Male')->count(),
            'female' => $gips->where('gender', 'Female')->count(),
        ]);
    }

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

    public function unassignedGip(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        return response()->json(
            GipEmployee::whereNull('office_id')
                ->whereYear('created_at', $year)
                ->get()
        );
    }

    public function assign(Request $request)
    {
        $request->validate([
            'gip_employee_id' => 'required|exists:gip_employees,id',
            'office_id'       => 'required|exists:gip_offices,id',   // table name
        ]);

        $gip = GipEmployee::findOrFail($request->gip_employee_id);
        $gip->office_id = $request->office_id;
        $gip->save();

        return response()->json(['success' => true, 'gip' => $gip]);
    }

    public function unassign(Request $request)
    {
        $request->validate([
            'gip_employee_id' => 'required|exists:gip_employees,id',
        ]);

        $gip = GipEmployee::findOrFail($request->gip_employee_id);
        $gip->office_id = null;
        $gip->save();

        return response()->json(['success' => true]);
    }

    public function officeGips($officeId, Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        return response()->json(
            GipEmployee::where('office_id', $officeId)
                ->whereYear('created_at', $year)
                ->get()
        );
    }

    // ── OFFICE CRUD ────────────────────────────────
    public function storeOffice(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:gip_offices,code',
        ]);

        $office = GipOffice::create($data);
        return response()->json($office, 201);
    }

    public function updateOffice(Request $request, $id)
    {
        $office = GipOffice::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:gip_offices,code,' . $office->id,
        ]);

        $office->update($data);
        return response()->json($office);
    }

    public function destroyOffice($id)
    {
        GipOffice::destroy($id);
        return response()->json(null, 204);
    }
}