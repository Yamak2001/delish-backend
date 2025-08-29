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
        // Refresh WhatsApp token daily at 2 AM
        $schedule->command('whatsapp:token refresh')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/whatsapp-token.log'));
            
        // Check token validity every 6 hours
        $schedule->command('whatsapp:token check')
            ->everySixHours()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/whatsapp-token-check.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}