<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->call(new \App\Cronjobs\RecurringInvoiceTask)->everyFifteenMinutes();
        $schedule->call(new \App\Cronjobs\DefferedInvoiceTask)->everyMinute();
        $schedule->call(new \App\Cronjobs\TrialEndedTask)->hourly();
        $schedule->call(new \App\Cronjobs\SubscriptionReminderTask)->hourlyAt(10);
        $schedule->call(new \App\Cronjobs\ResetQueueNumber)->dailyAt('00:00');
        $schedule->call(function () {
            update_currency_exchange_rate();
        })->everyTwoHours();

        $schedule->call(function () {
            update_option("cornjob_runs_at", now());
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
