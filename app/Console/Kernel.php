<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateMonthlyProductReports;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        GenerateMonthlyProductReports::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('calendar:send-reminders')->dailyAt('08:00');

              
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
