<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;

class ProductReportController extends Controller
{
    /**
     * Annual report for a single product (used by Supplyreport.vue)
     * GET /api/product-report/{productId}/{year}
     */
    public function getAnnualReport($productId, $year)
    {
        $product = Product::findOrFail($productId);

        $reports = ProductReport::where('product_id', $productId)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        $months = $reports->map(function ($r) {
            return [
                'month'        => $r->month,
                'year'         => $r->year,
                'price'        => $r->price,
                'starting_qty' => $r->starting_qty,
                'added_qty'    => $r->added_qty,
                'released_qty' => $r->released_qty,
                'remaining_qty'=> $r->remaining_qty,
                'total_cost'   => $r->total_cost, // Uses the accessor
            ];
        });

        if ($reports->isEmpty()) {
            return response()->json([
                'product' => $product,
                'year'    => (int) $year,
                'months'  => [],
                'summary' => [
                    'opening_stock'  => 0,
                    'total_added'    => 0,
                    'total_released' => 0,
                    'closing_stock'  => 0,
                    'avg_price'      => 0,
                ],
            ]);
        }

        $openingStock  = optional($reports->first())->starting_qty ?? 0;
        $totalAdded    = $reports->sum('added_qty');
        $totalReleased = $reports->sum('released_qty');
        $closingStock  = optional($reports->last())->remaining_qty ?? 0;
        $avgPrice      = $reports->avg('price') ?? 0;

        return response()->json([
            'product' => $product,
            'year'    => (int) $year,
            'months'  => $months,
            'summary' => [
                'opening_stock'  => $openingStock,
                'total_added'    => $totalAdded,
                'total_released' => $totalReleased,
                'closing_stock'  => $closingStock,
                'avg_price'      => round($avgPrice, 2),
            ],
        ]);
    }

    /**
     * Optional: all reports for a product across all years (if you still want /product-reports/{productId})
     */
    public function productReports($productId)
    {
        $product = Product::findOrFail($productId);

        $reports = ProductReport::where('product_id', $productId)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json(compact('product', 'reports'));
    }
}