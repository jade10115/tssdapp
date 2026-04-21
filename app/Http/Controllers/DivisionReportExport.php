<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\TupadAdlDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));
        
        $divisions = Division::with(['adlDetails' => function($query) use ($year) {
            $query->whereYear('created_at', $year);
        }])->get();

        $divisions->transform(function ($division) use ($year) {
            $adls = $division->adlDetails;
            $total_beneficiaries = $adls->sum('total_lgu');
            $total_amount = $adls->sum('amount');
            $balance = $adls->sum('balance');
            
            // Calculate percentage: (amount spent / total amount) * 100
            $percentage = 0;
            if ($total_amount > 0) {
                $percentage = (($total_amount - $balance) / $total_amount) * 100;
            }
            $percentage = round($percentage);

            // Set the computed properties
            $division->total_beneficiaries = $total_beneficiaries;
            $division->total_amount = $total_amount;
            $division->balance = $balance;
            $division->percentage = $percentage;
            $division->status = $this->getDivisionStatus($percentage);

            return $division;
        });

        return response()->json($divisions);
    }

    public function show($id, Request $request)
    {
        $year = $request->query('year', date('Y'));
        $division = Division::with(['adlDetails' => function($query) use ($year) {
            $query->whereYear('created_at', $year);
        }])->findOrFail($id);

        $adls = $division->adlDetails;
        $total_beneficiaries = $adls->sum('total_lgu');
        $total_amount = $adls->sum('amount');
        $balance = $adls->sum('balance');
        
        $percentage = 0;
        if ($total_amount > 0) {
            $percentage = (($total_amount - $balance) / $total_amount) * 100;
        }
        $percentage = round($percentage);

        // Set the computed properties
        $division->total_beneficiaries = $total_beneficiaries;
        $division->total_amount = $total_amount;
        $division->balance = $balance;
        $division->percentage = $percentage;

        return response()->json($division);
    }

    public function export(Request $request)
    {
        $year = $request->query('year', date('Y'));
        
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Division');
        $sheet->setCellValue('B1', 'Total Beneficiaries');
        $sheet->setCellValue('C1', 'Total Amount');
        $sheet->setCellValue('D1', 'Balance');
        $sheet->setCellValue('E1', 'Percentage');
        $sheet->setCellValue('F1', 'Status');
        $sheet->setCellValue('G1', 'Created By');
        
        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0077b6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Fetch divisions with year filter
        $divisions = Division::with(['adlDetails' => function($query) use ($year) {
            $query->whereYear('created_at', $year);
        }])->get();
        
        $row = 2; // Start from row 2 (after headers)
        
        foreach ($divisions as $division) {
            $adls = $division->adlDetails;
            $total_beneficiaries = $adls->sum('total_lgu');
            $total_amount = $adls->sum('amount');
            $balance = $adls->sum('balance');
            
            // Calculate percentage: (amount spent / total amount) * 100
            $percentage = 0;
            if ($total_amount > 0) {
                $percentage = (($total_amount - $balance) / $total_amount) * 100;
            }
            $percentage = round($percentage);
            
            $status = $this->getDivisionStatus($percentage);
            
            // Set cell values
            $sheet->setCellValue('A' . $row, $division->division);
            $sheet->setCellValue('B' . $row, $total_beneficiaries);
            $sheet->setCellValue('C' . $row, $total_amount);
            $sheet->setCellValue('D' . $row, $balance);
            $sheet->setCellValue('E' . $row, $percentage . '%');
            $sheet->setCellValue('F' . $row, $status);
            $sheet->setCellValue('G' . $row, $division->created_by);
            
            // Style data cells
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            
            // Format currency columns
            $sheet->getStyle('C' . $row . ':D' . $row)
                ->getNumberFormat()
                ->setFormatCode('"₱"#,##0.00');
            
            $row++;
        }
        
        // Add summary row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, '=SUM(B2:B' . ($row-1) . ')');
        $sheet->setCellValue('C' . $row, '=SUM(C2:C' . ($row-1) . ')');
        $sheet->setCellValue('D' . $row, '=SUM(D2:D' . ($row-1) . ')');
        
        // Style summary row
        $summaryStyle = [
            'font' => [
                'bold' => true
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f8f9fa']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($summaryStyle);
        
        // Auto-size columns
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // Create Excel writer and set filename
        $writer = new Xlsx($spreadsheet);
        $filename = "tupad-reports-{$year}.xlsx";
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];
        
        // Delete the spreadsheet object to free memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        // Return the file as a download response
        return response()->download($tempFile, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function getDivisionStatus($percentage)
    {
        if ($percentage >= 90) {
            return 'completed';
        } elseif ($percentage >= 50) {
            return 'active';
        } else {
            return 'pending';
        }
    }
}