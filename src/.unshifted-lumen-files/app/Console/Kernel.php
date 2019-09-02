<?php

namespace App\Console;

use App\Jobs\EnsureWikiDbPoolPopulatedJob;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\ExpireOldUserVerificationTokensJob;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\DispatchJob::class,
        \App\Console\Commands\HandleJob::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new EnsureWikiDbPoolPopulatedJob)->everyMinute();
        $schedule->job(new ExpireOldUserVerificationTokensJob)->hourly();
    }
}
