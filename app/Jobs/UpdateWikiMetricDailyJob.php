<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiDailyMetric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

//This job is for the daily measurements of metrics per wikibases.
//This is to help in understanding the purpose of active wikis.
//More metrics should be added to the job as needed.
class UpdateWikiMetricDailyJob implements ShouldQueue
{
    use Dispatchable;
    public int $timeout = 3600;

    protected Wiki $wiki;

    public function __construct($wiki)
    {
        $this->wiki = $wiki;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetric::where('wiki_id', $this->wiki->id)->latest('date')->first();
        $todayPageCount = $this->wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$this->wiki->deleted_at;
        if( !$oldRecord || $oldRecord->pages != $todayPageCount || !$oldRecord->is_deleted ) {
            WikiDailyMetric::create([
                'id' => $this->wiki->id . '_' . date('Y-m-d'),
                'pages' => $todayPageCount,
                'is_deleted' => $isDeleted,
                'date' => $today,
                'wiki_id' => $this->wiki->id,
            ]);

            \Log::info("New metric recorded for Wiki ID {$this->wiki->id}");
        } else {
            \Log::info("Metric unchanged for Wiki ID {$this->wiki->id}, no new record added.");
        }
    }
}
