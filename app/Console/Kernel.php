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
        // Jährliche Urlaubsübertragung am 1. Januar um 02:00 Uhr
        $schedule->command('vacation:carry-over')
            ->yearlyOn(1, 1, '02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Verfallen lassen von übertragenen Tagen am 1. April um 03:00 Uhr
        $schedule->command('vacation:expire-carry-over')
            ->yearlyOn(4, 1, '03:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Optional: Tägliche Prüfung auf verfallene Tage (falls gewünscht)
        // $schedule->command('vacation:expire-carry-over')
        //     ->daily()
        //     ->at('01:00');
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
