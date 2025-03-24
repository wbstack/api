<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiMonthlyMetrics;
use App\WikiQuarterlyMetrics;
use App\WikiWeeklyMetrics;

class WikiMetrics
{
    protected function thereIsNoUpdate($wiki, $oldRecord, Array $newRecord)
    {
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for WikiMetrics ID {$wiki->id}.");
                return true;
            }
            if (!$newRecord['isDeleted']) {
                if ($oldRecord->pages === $newRecord['pages']) {
                    \Log::info("Page count unchanged for WikiMetrics ID {$wiki->id}, no new record added.");
                    return true;
                }
            }
        }
        return false;
    }
    public function saveDailySnapshot(Wiki $wiki)
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        if($this->thereIsNoUpdate($wiki, $oldRecord, ['isDeleted'=>$isDeleted, 'pages'=>$todayPageCount])) return;
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
        ]);
        \Log::info("New daily metric recorded for WikiMetrics ID {$wiki->id}");
    }

    public function saveWeeklySnapshot(Wiki $wiki)
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiWeeklyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        if($this->thereIsNoUpdate($wiki, $oldRecord, ['isDeleted'=>$isDeleted, 'pages'=>$todayPageCount])) return;
        WikiWeeklyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
        ]);
        \Log::info("New weekly metric recorded for WikiMetrics ID {$wiki->id}");
    }

    public function saveMonthlySnapshot(Wiki $wiki)
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiMonthlyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        if($this->thereIsNoUpdate($wiki, $oldRecord, ['isDeleted'=>$isDeleted, 'pages'=>$todayPageCount])) return;
        WikiMonthlyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
        ]);
        \Log::info("New monthly metric recorded for WikiMetrics ID {$wiki->id}");
    }

    public function saveQuarterlySnapshot(Wiki $wiki)
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiQuarterlyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        if($this->thereIsNoUpdate($wiki, $oldRecord, ['isDeleted'=>$isDeleted, 'pages'=>$todayPageCount])) return;
        WikiQuarterlyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
        ]);
        \Log::info("New quarterly metric recorded for WikiMetrics ID {$wiki->id}");
    }
}

