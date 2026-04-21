<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\TupadDetail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TupadExportController extends Controller
{
    public function exportExcel()
    {
        $items = TupadDetail::with('division')->orderBy('created_at', 'DESC')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Division');
        $sheet->setCellValue('C1', 'No. of Workers');
        $sheet->setCellValue('D1', 'Amount');
        $sheet->setCellValue('E1', 'Created At');

        $row = 2;

        foreach ($items as $item) {
            $sheet->setCellValue("A$row", $item->id);
            $sheet->setCellValue("B$row", $item->division->division_name ?? '');
            $sheet->setCellValue("C$row", $item->no_of_workers);
            $sheet->setCellValue("D$row", $item->amount);
            $sheet->setCellValue("E$row", $item->created_at->format('Y-m-d H:i'));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=TUPAD_Report.xlsx",
        ]);
    }
}
