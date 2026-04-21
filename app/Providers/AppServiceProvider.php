<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Laravel 11+ requires tasks to be registered here,
         * NOT inside app/Console/Kernel.php
         */
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {

            // Run command every 1st day of the month at 00:10 AM
            $schedule->command('reports:generate-monthly')
                     ->monthlyOn(1, '00:10');

        });
    }
}
