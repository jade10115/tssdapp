<?php

namespace App\Http\Controllers;

use App\Models\TupadAdlMaster;
use Illuminate\Http\Request;

class TupadAdlMasterController extends Controller
{
    // GET /api/tupad_adl_masters?year=2025
    public function index(Request $request)
    {
        $q = TupadAdlMaster::query()->with([
            'details:id,adl_master_id,amount,balance',
            'details.breakdowns:id,adl_detail_id,lgu,amount,beneficiaries',
        ]);

        if ($request->filled('year')) {
            $q->whereYear('created_at', (int) $request->query('year'));
        }

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        $items = $q->orderByDesc('id')
            ->get()
            ->map(fn (TupadAdlMaster $m) => $this->transformMaster($m))
            ->values();

        return response()->json($items);
    }

    // POST /api/tupad_adl_masters
    public function store(Request $request)
    {
        if ($request->has('total_amount')) {
            $request->merge([
                'total_amount' => $this->parseMoney($request->input('total_amount')),
            ]);
        }

        $validated = $request->validate([
            'adl'          => 'required|string|max:191',
            'sponsor'      => 'nullable|string|max:191',
            'total_amount' => 'required|numeric|min:0',
            'status'       => 'nullable|string|max:50',
        ]);

        $total = round((float) $validated['total_amount'], 2);

        $m = TupadAdlMaster::create([
            'adl'          => trim($validated['adl']),
            'sponsor'      => filled($validated['sponsor'] ?? null) ? trim($validated['sponsor']) : null,
            'total_amount' => $total,
            'balance'      => $total,
            'status'       => $this->normalizeStatus($validated['status'] ?? 'Open'),
        ]);

        return response()->json(
            $this->transformMaster($m->fresh(['details.breakdowns'])),
            201
        );
    }

    // PUT /api/tupad_adl_masters/{id}
    public function update(Request $request, $id)
    {
        $m = TupadAdlMaster::with(['details.breakdowns'])->findOrFail($id);

        if ($request->has('total_amount')) {
            $request->merge([
                'total_amount' => $this->parseMoney($request->input('total_amount')),
            ]);
        }

        $validated = $request->validate([
            'adl'          => 'required|string|max:191',
            'sponsor'      => 'nullable|string|max:191',
            'total_amount' => 'required|numeric|min:0',
            'status'       => 'nullable|string|max:50',
        ]);

        $newTotal = round((float) $validated['total_amount'], 2);

        // IMPORTANT:
        // use ACTUAL spent from breakdowns/details first,
        // fallback to old_total - old_balance only when no breakdown/detail amount exists
        $spent = $this->resolveSpent($m);
        if ($spent > $newTotal) {
            $spent = $newTotal;
        }

        $newBalance = round(max($newTotal - $spent, 0), 2);

        $m->update([
            'adl'          => trim($validated['adl']),
            'sponsor'      => filled($validated['sponsor'] ?? null) ? trim($validated['sponsor']) : null,
            'total_amount' => $newTotal,
            'balance'      => $newBalance,
            'status'       => $this->normalizeStatus($validated['status'] ?? $m->status),
        ]);

        return response()->json(
            $this->transformMaster($m->fresh(['details.breakdowns']))
        );
    }

    // DELETE /api/tupad_adl_masters/{id}
    public function destroy($id)
    {
        $m = TupadAdlMaster::findOrFail($id);
        $m->delete();

        return response()->json(['message' => 'Deleted']);
    }

    protected function transformMaster(TupadAdlMaster $m): array
    {
        $m->loadMissing(['details.breakdowns']);

        $details = $m->details ?? collect();
        $breakdowns = $details->flatMap(function ($detail) {
            return $detail->breakdowns ?? collect();
        });

        $total = round((float) ($m->total_amount ?? 0), 2);
        $storedBalance = round((float) ($m->balance ?? 0), 2);

        // UNIQUE LGU COUNT (case-insensitive)
        $lguMap = [];
        foreach ($breakdowns as $b) {
            $label = trim((string) ($b->lgu ?? ''));
            if ($label !== '') {
                $lguMap[mb_strtolower($label)] = $label;
            }
        }
        $lguNames = array_values($lguMap);
        $uniqueLguCount = count($lguNames);

        $beneficiaries = (int) $breakdowns->sum(function ($b) {
            return (int) ($b->beneficiaries ?? 0);
        });

        $spent = $this->resolveSpent($m);
        if ($spent > $total) {
            $spent = $total;
        }

        $computedBalance = round(max($total - $spent, 0), 2);
        $utilization = $total > 0 ? round(($spent / $total) * 100, 2) : 0;

        return [
            'id'             => $m->id,
            'adl'            => $m->adl,
            'sponsor'        => $m->sponsor,
            'total_amount'   => $total,
            'balance'        => $computedBalance,   // return computed balance for UI
            'stored_balance' => $storedBalance,     // optional reference
            'status'         => $this->normalizeStatus($m->status),
            'created_at'     => optional($m->created_at)?->toDateTimeString(),
            'updated_at'     => optional($m->updated_at)?->toDateTimeString(),

            '_spent'         => round($spent, 2),
            '_total_lgu'     => $uniqueLguCount,
            '_lgu_names'     => $lguNames,
            '_beneficiaries' => $beneficiaries,
            '_utilization'   => $utilization,
        ];
    }

    protected function resolveSpent(TupadAdlMaster $m): float
    {
        $m->loadMissing(['details.breakdowns']);

        $details = $m->details ?? collect();
        $breakdowns = $details->flatMap(function ($detail) {
            return $detail->breakdowns ?? collect();
        });

        // 1) use breakdown amount first
        $breakdownSpent = (float) $breakdowns->sum(function ($b) {
            return (float) ($b->amount ?? 0);
        });

        if ($breakdownSpent > 0) {
            return round($breakdownSpent, 2);
        }

        // 2) fallback to detail amount if no breakdown amount yet
        $detailSpent = (float) $details->sum(function ($d) {
            return (float) ($d->amount ?? 0);
        });

        if ($detailSpent > 0) {
            return round($detailSpent, 2);
        }

        // 3) final fallback to stored total - balance
        $fallback = (float) ($m->total_amount ?? 0) - (float) ($m->balance ?? 0);

        return round(max($fallback, 0), 2);
    }

    protected function parseMoney($value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($clean) ? round((float) $clean, 2) : 0.00;
    }

    protected function normalizeStatus(?string $status): string
    {
        $v = strtolower(trim((string) $status));

        if (in_array($v, ['open', 'active', 'enabled'], true)) {
            return 'Open';
        }

        if (in_array($v, ['closed', 'inactive', 'disabled'], true)) {
            return 'Closed';
        }

        return 'Pending';
    }
}