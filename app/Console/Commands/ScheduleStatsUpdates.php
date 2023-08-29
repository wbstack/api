<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SiteStatsUpdateJob;
use App\Wiki;
use App\Jobs\PlatformStatsSummaryJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
/**
 * Schedules jobs for updating site_stats per wiki then platformsummaryjob
 */
class ScheduleStatsUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules updates for wiki site_stats then runs platform summary job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $siteStatsUpdateJobs = [];
        foreach (Wiki::all() as $wiki) {
            $siteStatsUpdateJobs[] = new SiteStatsUpdateJob($wiki->id);
        }

        Log::info(__METHOD__ . ": Scheduling updates for " . count($siteStatsUpdateJobs) . " wikis.");

        Bus::batch($siteStatsUpdateJobs)
            ->allowFailures()
            ->then(function () {
                dispatch(new PlatformStatsSummaryJob());
            })->dispatch();
        return 0;
    }
}
