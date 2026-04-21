<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\ProductReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnnualReportExport implements FromArray, WithHeadings
{
    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            'Product',
            'Unit',
            'Month',
            'Year',
            'Unit Cost',
            'Opening Qty',
            'Qty Received',
            'Qty Issued',
            'Balance Qty',
            'Total Cost (Issued)',
        ];
    }

    public function array(): array
    {
        $rows = [];

        $products = Product::with(['reports' => function ($q) {
            $q->orderBy('year')->orderBy('month');
        }])->get();

        foreach ($products as $product) {
            foreach ($product->reports->where('year', $this->year) as $r) {
                $rows[] = [
                    $product->product_name,
                    $product->unit,
                    $r->month,
                    $r->year,
                    $r->price,
                    $r->starting_qty,
                    $r->added_qty,
                    $r->released_qty,
                    $r->remaining_qty,
                    $r->price * $r->released_qty,
                ];
            }
        }

        return $rows;
    }
}
