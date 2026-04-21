<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use Illuminate\Http\Request;

class SupplyRequestController extends Controller
{
public function index()
{
    return Checkout::with(['userProfile.position'])
        ->get()
        ->map(function ($checkout) {
            $profile = $checkout->userProfile;

            return [
                'id' => $checkout->id,
                'user_id' => $checkout->user_id,
                'name' => $profile->full_name ?? "Unknown",
                // ✅ FIX HERE
                'designation' => $profile?->position?->position ?? "",
                'division' => $profile->division ?? "",
                'approved_by_id' => $checkout->approved_by_id,
                'issued_by_id' => $checkout->issued_by_id,
                'status' => ucfirst($checkout->status),
                'date_requested' => $checkout->created_at->format('F d, Y h:i A'),
            ];
        });
}

public function viewItems($id)
{
    $checkout = Checkout::with(['items.product', 'userProfile.position'])->findOrFail($id);

    return [
        'id' => $checkout->id,
        'user_id' => $checkout->user_id,
        'name' => $checkout->userProfile?->full_name ?? 'Unknown',
        'designation' => $checkout->userProfile?->position?->position ?? "",
        'division' => $checkout->userProfile?->division ?? "",
        'approved_by_id' => $checkout->approved_by_id,
        'issued_by_id' => $checkout->issued_by_id,
        'status' => $checkout->status,
        'date_requested' => optional($checkout->created_at)->format("F d, Y h:i A"),

        'items' => $checkout->items->map(fn($item) => [
            'id' => $item->id,
            'product_name' => $item->product?->product_name ?? '',
            'quantity' => $item->quantity,
            'approved_qty' => $item->approved_qty ?? 0,
            'status' => $item->status,
            'price' => $item->price,
            'unit' => $item->unit ?? ($item->product?->unit ?? ''),
            'image' => $item->product?->image
                ? asset('product-images/'.$item->product->image)
                : null,
        ])->values(),
    ];
}
public function saveApprovedBy($id, Request $request)
{
    $request->validate(['approved_by_id' => 'required|integer']);

    $checkout = Checkout::findOrFail($id);
    $checkout->approved_by_id = $request->approved_by_id;
    $checkout->save();

    return ['message' => 'Approved by saved'];
}

public function saveIssuedBy($id, Request $request)
{
    $request->validate(['issued_by_id' => 'required|integer']);

    $checkout = Checkout::findOrFail($id);
    $checkout->issued_by_id = $request->issued_by_id;
    $checkout->save();

    return ['message' => 'Issued by saved'];
}
public function updateItemStatus(Request $request, $itemId)
{
$request->validate([
    'status' => 'required|in:Pending,Approved,Rejected',
    'approved_qty' => 'nullable|integer|min:0',
]);

$item = CheckoutItem::with('product')->find($itemId);

if (!$item) {
    return response()->json(['message' => 'Item not found'], 404);
}

// Update the stock & item
$oldQty = $item->approved_qty ?? 0;
$newQty = $request->approved_qty ?? 0;
$difference = $newQty - $oldQty;

$product = $item->product;

// Adjust stock
if ($difference > 0) {
    // reduce stock
    $product->current_stock -= $difference;
} else {
    // return stock
    $product->current_stock += abs($difference);
}

$product->save();

$item->approved_qty = $newQty;
$item->status = $request->status;
$item->save();

return response()->json(['message' => 'Item updated successfully']);
}

// Add this method in the SupplyRequestController
public function updateStatus(Request $request, $id)
{
$request->validate(['status' => 'required|in:Pending,Accepted,Rejected']);

$checkout = Checkout::findOrFail($id);
$checkout->status = $request->status;
$checkout->save();

return response()->json(['message' => 'Request status updated successfully']);
}


}

