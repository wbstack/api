<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NightlyRunScheduledWikiSuspension implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // for each Wiki
        // if it has a scheduledSuspension
        // and that scheduled suspension
        // and that should be active by comparison with local time
        // and there is no submitted or picked-up review review submission
        // then in a single transaction
        // create an actual suspension
        // with an expiry time of now
        // and the corresponding reason from the scheduled suspension
        // and remove the scheduled suspension
    }
}
