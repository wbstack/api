<?php

namespace App\Console;

use App\Jobs\EnsureWikiDbPoolPopulatedJob;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\ExpireOldUserVerificationTokensJob;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
      // TODO SHIFT is this needed? I see the commands() function below
      // that might do automagic loading?
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

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
