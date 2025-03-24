<?php

namespace App\Jobs;

use App\Metrics\App\WikiMetrics;
use App\Wiki;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateWikiWeeklyMetricJob extends Job implements ShouldBeUnique
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
            (new WikiMetrics())->saveWeeklySnapshot($wiki);
        }
    }
}
