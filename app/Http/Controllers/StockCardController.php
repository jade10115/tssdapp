<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;
use Barryvdh\DomPDF\Facade\Pdf;

class StockCardController extends Controller
{
    /**
     * Generate STOCK CARD PDF (DOE style) for a single product
     * GET /api/stock-card/{productId}
     */
    public function generate($productId)
    {
        $product = Product::findOrFail($productId);

        $reports = ProductReport::where('product_id', $productId)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $pdf = Pdf::loadView('reports.stock_card', [
            'product' => $product,
            'reports' => $reports,
        ])->setPaper('legal', 'landscape'); // landscape per your request

        return $pdf->download("STOCK_CARD_{$product->id}.pdf");
    }
}
