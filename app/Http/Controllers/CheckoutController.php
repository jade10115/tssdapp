<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Checkout;
use App\Models\CheckoutItem;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:tbl_products,id', // ✅ match your table name
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string',
            'total_price' => 'nullable|numeric'
        ]);

        try {
            DB::beginTransaction();

            // ✅ Create main checkout record
            $checkout = Checkout::create([
                'user_id' => $request->user_id,
                'total_price' => $request->total_price ?? 0,
            ]);

            // ✅ Loop through each item in the checkout
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // ✅ Check stock
                if ($product->current_stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->product_name}");
                }

                // ✅ Reduce stock
                $product->current_stock -= $item['quantity'];
                $product->save();

                // ✅ Insert into checkout_items
                CheckoutItem::create([
                    'checkout_id'   => $checkout->id,
                    'product_id'    => $item['product_id'],
                    'quantity'      => $item['quantity'],
                    'price'         => $item['price'],
                    'unit'          => $item['unit'] ?? $product->unit, // fallback
                    'approved_qty'  => $item['quantity'],
                    'status'        => 'Pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Checkout successful!',
                'checkout_id' => $checkout->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Checkout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
