<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('contracts:scan')->daily();
        $schedule->command('reports:check-expired')->hourly();
        $schedule->command('invoices:send-overdue-reminders')->dailyAt('08:00');
        //chạy gói hết hạn
        $schedule->command('subscriptions:notify-expiring')->dailyAt('08:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
