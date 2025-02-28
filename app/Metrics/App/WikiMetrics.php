<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDailyMetrics;

class WikiMetrics
{

    public function saveMetrics(Wiki $wiki): void
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for WikiMetrics ID {$wiki->id}.");
                return;
            }
            if (!$isDeleted) {
                if ($oldRecord->pages === $todayPageCount) {
                    \Log::info("Page count unchanged for WikiMetrics ID {$wiki->id}, no new record added.");
                    return;
                }
            }
        }
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
        ]);
        \Log::info("New metric recorded for WikiMetrics ID {$wiki->id}");
    }

}

