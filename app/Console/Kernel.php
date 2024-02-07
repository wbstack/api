<?php

namespace App\Console;

use App\Jobs\ExpireOldUserVerificationTokensJob;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\PruneEventPageUpdatesTable;
use App\Jobs\PruneQueryserviceBatchesTable;
use App\Jobs\RequeuePendingQsBatchesJob;
use App\Jobs\SandboxCleanupJob;
use App\Jobs\PollForMediaWikiJobsJob;
use App\Jobs\UpdateWikiSiteStatsJob;
use App\Jobs\SendEmptyWikiNotificationsJob;
use App\Jobs\CreateQueryserviceBatchesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     */
    protected function schedule(Schedule $schedule): void
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
        $schedule->job(new CreateQueryserviceBatchesJob)->everyMinute();
        $schedule->job(new RequeuePendingQsBatchesJob)->everyFifteenMinutes();

        // Sandbox
        // TODO this should maybe only be run when sandbox as a whole is loaded?
        // TODO instead of using LOAD ROUTES, we should just have different modes?
        $schedule->job(new SandboxCleanupJob)->everyFifteenMinutes();

        // Schedule site stat updates for each wiki and platform-summary
        $schedule->command('schedule:stats')->dailyAt('7:00');

        // https://laravel.com/docs/10.x/upgrade#redis-cache-tags
        $schedule->command('cache:prune-stale-tags')->hourly();

        $schedule->job(new PollForMediaWikiJobsJob)->everyFifteenMinutes();

        $schedule->job(new UpdateWikiSiteStatsJob)->dailyAt('19:00');

        $schedule->job(new SendEmptyWikiNotificationsJob)->dailyAt('21:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Commands/User');
        $this->load(__DIR__.'/Commands/Job');
        $this->load(__DIR__.'/Commands/Wiki');
        $this->load(__DIR__.'/Commands/Invitation');

        require base_path('routes/console.php');
    }
}
