<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * LIST ALL PRODUCTS
     */
    public function index(Request $request)
{
    $query = Product::with('reports');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    return $query->latest()->get();
}


    /**
     * SHOW SINGLE PRODUCT
     */
    public function show($id)
    {
        return response()->json(
            Product::with('reports')->findOrFail($id)
        );
    }

    /**
     * CREATE PRODUCT
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name'  => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'unit'          => 'required|string|max:50',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload
        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('product-images'), $imageName);
        }

        // 🔐 user_id is SAFE because route is auth:sanctum
        $product = Product::create([
            'user_id'       => auth()->id(),
            'product_name'  => $request->product_name,
            'price'         => $request->price,
            'current_stock' => $request->current_stock,
            'unit'          => $request->unit,
            'image'         => $imageName,
            'status'        => $this->computeStatus($request->current_stock),
        ]);

        $this->recordMonthlyReport($product, true);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, $id)
    {
        $product  = Product::findOrFail($id);
        $oldQty   = $product->current_stock;

        $request->validate([
            'product_name'  => 'sometimes|string|max:255',
            'price'         => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
            'unit'          => 'sometimes|string|max:50',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('product-images'), $imageName);
            $product->image = $imageName;
        }

        if ($request->filled('product_name')) {
            $product->product_name = $request->product_name;
        }

        if ($request->filled('price')) {
            $product->price = $request->price;
        }

        if ($request->filled('current_stock')) {
            $product->current_stock = $request->current_stock;
            $product->status = $this->computeStatus($request->current_stock);
        }

        if ($request->filled('unit')) {
            $product->unit = $request->unit;
        }

        $product->save();

        $this->recordMonthlyReport($product, false, $oldQty);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    /**
     * DELETE PRODUCT
     */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * PRODUCT REPORT
     */
    public function productReport($id)
    {
        $product = Product::findOrFail($id);

        return response()->json(
            ProductReport::where('product_id', $id)
                ->orderBy('year')
                ->orderBy('month')
                ->get()
        );
    }

    /* ================= HELPERS ================= */

    private function computeStatus(int $qty): string
    {
        if ($qty <= 0) return 'Not Available';
        if ($qty < 5)  return 'Low Qty';
        return 'Available';
    }

    private function recordMonthlyReport(
        Product $product,
        bool $isNew = false,
        ?int $oldQty = null
    ): void {
        $month = now()->month;
        $year  = now()->year;

        $report = ProductReport::firstOrCreate(
            [
                'product_id' => $product->id,
                'month'      => $month,
                'year'       => $year,
            ],
            [
                'starting_qty'  => $product->current_stock,
                'remaining_qty' => $product->current_stock,
                'price'         => $product->price,
            ]
        );

        if (!$isNew && $oldQty !== null) {
            $added    = max(0, $product->current_stock - $oldQty);
            $released = max(0, $oldQty - $product->current_stock);

            $report->added_qty     += $added;
            $report->released_qty  += $released;
            $report->remaining_qty  = $product->current_stock;
            $report->save();
        }
    }
}
