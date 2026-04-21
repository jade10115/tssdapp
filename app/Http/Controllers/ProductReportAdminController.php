<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ProductReportAdminController extends Controller
{
    public function generateMonthly(Request $request)
    {
        // Optional inputs (safe defaults)
        $force = (bool)($request->input('force', true));
        $year  = $request->input('year'); // optional
        $month = $request->input('month'); // optional

        // Run artisan command and capture exit code + output
        $args = [];
        if ($force) $args['--force'] = true;
        if ($year)  $args['--year'] = (int)$year;
        if ($month) $args['--month'] = (int)$month;

        Artisan::call('reports:generate-monthly', $args);
        $output = Artisan::output();

        // Return parsed JSON if command prints JSON, else raw output
        // We'll assume the command prints JSON at the end (see command below).
        $decoded = json_decode(trim($output), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return response()->json($decoded);
        }

        return response()->json([
            'month_label' => 'Monthly Snapshot',
            'generated' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'raw_output' => $output,
        ]);
    }
}
