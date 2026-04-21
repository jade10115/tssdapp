<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TupadAdlDetail;
use App\Models\TupadAdlMaster;
use App\Models\TupadAdlBreakdown;
use Illuminate\Support\Facades\DB;

class TupadAdlDetailsController extends Controller
{
    /**
     * Display a listing of ADL details for a division.
     */
    public function index(Request $request)
    {
        $divisionId = $request->division_id;
        $dateFrom = $request->from; // optional date filter
        $dateTo   = $request->to;   // optional date filter

        $query = TupadAdlDetail::with(['master', 'breakdowns'])
            ->where('division_id', $divisionId);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $adls = $query->get();

        return response()->json($adls);
    }

    /**
     * Store a newly created ADL detail.
     */
    public function store(Request $request)
    {
        $request->validate([
            'division_id'   => 'required|integer|exists:divisions,id',
            'adl_master_id' => 'required|integer|exists:tupad_adl_masters,id',
            'amount'        => 'required|numeric|min:0.01',
        ]);

        $master = TupadAdlMaster::findOrFail($request->adl_master_id);

        // Ensure amount does not exceed master balance
        if ($request->amount > $master->balance) {
            return response()->json([
                'error' => 'Amount exceeds master available balance.'
            ], 422);
        }

        $adlDetail = TupadAdlDetail::create([
            'division_id'    => $request->division_id,
            'adl_master_id'  => $request->adl_master_id,
            'adl'            => $request->adl ?? $master->adl,
            'sponsor'        => $request->sponsor ?? $master->sponsor,
            'amount'         => $request->amount,
            'balance'        => $request->amount,
            'total_lgu'      => 0,
            'percentage'     => 0,
        ]);

        // Deduct from master balance
        $master->balance -= $request->amount;
        $master->save();

        return response()->json($adlDetail, 201);
    }

    /**
     * Display a single ADL detail.
     */
    public function show($id)
    {
        $adl = TupadAdlDetail::with(['master', 'breakdowns'])->findOrFail($id);
        return response()->json($adl);
    }

    /**
     * Update the specified ADL detail.
     */
    public function update(Request $request, $id)
    {
        $adl = TupadAdlDetail::findOrFail($id);
        $master = TupadAdlMaster::findOrFail($adl->adl_master_id);

        $request->validate([
            'adl'     => 'nullable|string|max:255',
            'sponsor' => 'nullable|string|max:255',
            'amount'  => 'required|numeric|min:0.01',
        ]);

        $oldAmount = $adl->amount;
        $alreadySpent = max(0, $oldAmount - $adl->balance);

        // Validate new amount cannot be lower than already spent
        if ($request->amount < $alreadySpent) {
            return response()->json([
                'error' => "New amount cannot be lower than already spent ($alreadySpent)."
            ], 422);
        }

        // Maximum allowed increase is master balance
        $maxAllowed = $oldAmount + $master->balance;
        if ($request->amount > $maxAllowed) {
            return response()->json([
                'error' => "New amount cannot exceed allowed maximum ($maxAllowed)."
            ], 422);
        }

        // Update balances
        $adl->adl = $request->adl ?? $adl->adl;
        $adl->sponsor = $request->sponsor ?? $adl->sponsor;
        $adl->balance = $adl->balance + ($request->amount - $oldAmount); // adjust balance
        $adl->amount = $request->amount;
        $adl->save();

        // Adjust master balance accordingly
        $master->balance -= ($request->amount - $oldAmount);
        $master->save();

        return response()->json($adl);
    }

    /**
     * Remove the specified ADL detail.
     */
    public function destroy($id)
    {
        $adl = TupadAdlDetail::findOrFail($id);
        $master = TupadAdlMaster::findOrFail($adl->adl_master_id);

        // Return remaining balance to master
        $master->balance += $adl->balance;
        $master->save();

        $adl->delete();

        return response()->json([
            'message' => 'ADL detail deleted successfully.'
        ]);
    }

    /**
 * Mark an ADL detail as received by LGU.
 */
public function markAsReceived(Request $request, $id)
{
    $request->validate([
        'received_amount' => 'required|numeric|min:0.01',
    ]);

    $adl = TupadAdlDetail::findOrFail($id);

    $received = $request->received_amount;

    if ($received > $adl->balance) {
        return response()->json([
            'error' => 'Received amount cannot exceed ADL remaining balance.',
        ], 422);
    }

    // Deduct received from balance
    $adl->balance -= $received;

    // Recompute percentage utilization
    $adl->percentage = $adl->amount > 0
        ? round((($adl->amount - $adl->balance) / $adl->amount) * 100)
        : 0;

    // Increment total_lgu if full LGU received
    if ($adl->balance <= 0) {
        $adl->total_lgu += 1;
    }

    $adl->save();

    return response()->json([
        'message' => 'ADL marked as received successfully.',
        'adl' => $adl,
    ]);
}
}