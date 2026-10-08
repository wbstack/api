<?php

namespace App\Jobs;

use App\Metrics\App\WikiMetrics;
use App\Wiki;
use Illuminate\Contracts\Queue\ShouldBeUnique;

// This job is for the daily measurements of metrics per wikibases.
// This is to help in understanding the purpose of active wikis.
class UpdateWikiDailyMetricJob extends Job implements ShouldBeUnique {
    public $timeout = 3600;

    /**
     * Execute the job.
     */
    public function handle(): void {
        $wikis = Wiki::withTrashed()->get();
        foreach ($wikis as $wiki) {
            (new WikiMetrics())->saveMetrics($wiki);
        }
    }
}
