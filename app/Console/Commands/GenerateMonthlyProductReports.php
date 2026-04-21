<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductReport;
use Carbon\Carbon;

class GenerateMonthlyProductReports extends Command
{
    protected $signature = 'reports:generate-monthly
        {--force : Run even if not the 1st day of the month}
        {--month= : Target month (1-12). Default: previous month}
        {--year= : Target year. Default: previous month year}';

    protected $description = 'Generate or update product inventory snapshot (monthly) for all products';

    public function handle()
    {
        $now = Carbon::now();

        $force = (bool)$this->option('force');

        // Default month/year is previous month
        $target = $now->copy()->subMonth();
        $month = (int)($this->option('month') ?: $target->month);
        $year  = (int)($this->option('year')  ?: $target->year);

        // If not force, only run on 1st day
        if (!$force && $now->day !== 1) {
            $payload = [
                'month_label' => Carbon::create($year, $month, 1)->format('F Y'),
                'generated' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
                'message' => 'Skipped: not the 1st day of the month (use --force to run manually).'
            ];
            $this->line(json_encode($payload));
            return Command::SUCCESS;
        }

        $products = Product::all();
        $generated = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                // Get last report (for starting_qty)
                $lastReport = ProductReport::where('product_id', $product->id)
                    ->orderByDesc('year')
                    ->orderByDesc('month')
                    ->first();

                $startingQty = $lastReport ? (int)$lastReport->remaining_qty : (int)$product->current_stock;
                $currentQty  = (int)$product->current_stock;

                $netChange = $currentQty - $startingQty;
                $addedQty = $netChange > 0 ? $netChange : 0;
                $releasedQty = $netChange < 0 ? abs($netChange) : 0;

                $remainingQty = $startingQty + $addedQty - $releasedQty;

                // ✅ Update or create report
                $existing = ProductReport::where([
                    'product_id' => $product->id,
                    'month' => $month,
                    'year' => $year,
                ])->first();

                ProductReport::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'price' => $product->price,
                        'starting_qty' => $startingQty,
                        'added_qty' => $addedQty,
                        'released_qty' => $releasedQty,
                        'remaining_qty' => $remainingQty,
                    ]
                );

                if ($existing) $updated++;
                else $generated++;

            } catch (\Exception $e) {
                $errors[] = "Product {$product->id}: {$e->getMessage()}";
            }
        }

        $payload = [
            'month_label' => Carbon::create($year, $month, 1)->format('F Y'),
            'generated' => $generated,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];

        // IMPORTANT: return JSON so the UI can show summary
        $this->line(json_encode($payload));

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
