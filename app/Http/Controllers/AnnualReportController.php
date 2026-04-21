<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnnualReportExport;

class AnnualReportController extends Controller
{
    /**
     * Annual PDF in landscape DOE-style layout
     * GET /api/reports/annual/pdf/{year}
     */
    public function generatePdf($year)
    {
        $products = Product::with(['reports' => function ($q) use ($year) {
            $q->where('year', $year)->orderBy('month');
        }])->get();

        $pdf = Pdf::loadView('reports.annual', [
            'year'     => $year,
            'products' => $products,
        ])->setPaper('legal', 'landscape');

        return $pdf->download("ANNUAL_SUPPLY_REPORT_{$year}.pdf");
    }

    /**
     * Annual Excel
     * GET /api/reports/annual/excel/{year}
     */
    public function generateExcel($year)
    {
        return Excel::download(new AnnualReportExport($year), "ANNUAL_SUPPLY_REPORT_{$year}.xlsx");
    }
}
