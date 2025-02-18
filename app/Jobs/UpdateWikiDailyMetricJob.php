<?php

namespace App\Jobs;

use App\Wiki;
use \App\Metrics\App\WikiMetrics;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

//This job is for the daily measurements of metrics per wikibases.
//This is to help in understanding the purpose of active wikis.
class UpdateWikiDailyMetricJob extends Job implements ShouldBeUnique
{
    use Dispatchable;
    public $timeout = 3600;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $wikis= Wiki::withTrashed()->get();
        foreach ( $wikis as $wiki ) {
            (new WikiMetrics())->saveMetrics($wiki);
        }
    }
}
