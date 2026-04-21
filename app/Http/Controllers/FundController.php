<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use Illuminate\Http\Request;

class FundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Map DB columns to frontend fields
        return Fund::all()->map(function ($fund) {
            return [
                'id' => $fund->id,
                'adl' => $fund->adl,
                'sponsor' => $fund->source_name,
                'amount' => $fund->total_amount,
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'adl' => 'required|string|max:255',
            'sponsor' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $fund = Fund::create([
            'adl' => $request->adl,
            'source_name' => $request->sponsor,
            'total_amount' => $request->amount,
        ]);

        return response()->json([
            'id' => $fund->id,
            'adl' => $fund->adl,
            'sponsor' => $fund->source_name,
            'amount' => $fund->total_amount,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fund $fund)
    {
        $request->validate([
            'adl' => 'required|string|max:255',
            'sponsor' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $fund->update([
            'adl' => $request->adl,
            'source_name' => $request->sponsor,
            'total_amount' => $request->amount,
        ]);

        return response()->json([
            'id' => $fund->id,
            'adl' => $fund->adl,
            'sponsor' => $fund->source_name,
            'amount' => $fund->total_amount,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fund $fund)
    {
        $fund->delete();

        return response()->json(['message' => 'Fund deleted successfully']);
    }
}
