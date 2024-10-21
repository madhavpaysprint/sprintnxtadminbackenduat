<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\sendReport',
        'App\Console\Commands\sendReportMail',
        'App\Console\Commands\sendUpiReport',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('transaction:report')->daily();
        $schedule->command('mail:report')->daily();
        $schedule->command('upitransaction:report')->everyMinute();
    }
}
