<?php

namespace App\Console;

use App\Jobs\ExpireOldUserVerificationTokensJob;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\PruneEventPageUpdatesTable;
use App\Jobs\PruneQueryserviceBatchesTable;
use App\Jobs\SandboxCleanupJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\PlatformStatsSummaryJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Make sure that the DB and QS pools are always populated somewhat.
        // This will create at most 1 new entry for each per minute...
        // There are also jobs currently scheduled in Controllers that use up resources from these pools
        // for more opportunistic storage repopulation
        $schedule->job(new ProvisionWikiDbJob(null, null, 10))->everyMinute();
        $schedule->job(new ProvisionQueryserviceNamespaceJob(null, 10))->everyMinute();

        // Slowly cleanup some tables
        $schedule->job(new ExpireOldUserVerificationTokensJob)->hourly();
        $schedule->job(new PruneEventPageUpdatesTable)->everyFifteenMinutes();
        $schedule->job(new PruneQueryserviceBatchesTable)->everyFifteenMinutes();

        // Sandbox
        // TODO this should maybe only be run when sandbox as a whole is loaded?
        // TODO instead of using LOAD ROUTES, we should just have different modes?
        $schedule->job(new SandboxCleanupJob)->everyFifteenMinutes();

        // platform summary stats job
        $schedule->job(new PlatformStatsSummaryJob())->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Commands/User');
        $this->load(__DIR__.'/Commands/Job');
        $this->load(__DIR__.'/Commands/Wiki');
        $this->load(__DIR__.'/Commands/Invitation');

        require base_path('routes/console.php');
    }
}
