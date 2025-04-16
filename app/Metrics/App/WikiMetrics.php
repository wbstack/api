<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDb;
use App\WikiDailyMetrics;
use Illuminate\Support\Facades\DB;

class WikiMetrics
{
    const INTERVAL_DAILY = 'INTERVAL 1 DAY';
    const INTERVAL_WEEKLY = ' INTERVAL 1 WEEK';
    const INTERVAL_MONTHLY = 'INTERVAL 1 MONTH';
    const INTERVAL_QUARTERLY = 'INTERVAL 3 MONTH';

    const QUERY_NUMBER_OF_ACTIONS = <<<EOF
SELECT
    SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), ?), '%Y%m%d%H%i%S')) AS sum_actions,
FROM
    ? AS rc
INNER JOIN ? AS a ON rc.rc_actor = a.actor_id
// Conditions below added for consistency with Wikidata: https://phabricator.wikimedia.org/diffusion/ADES/browse/master/src/wikidata/site_stats/sql/active_user_changes.sql
AND a.actor_user != 0
AND rc.rc_bot = 0
AND ( rc.rc_log_type != 'newusers' OR rc.rc_log_type IS NULL)
EOF;

    protected $wiki;

    public function getData(): array
    {
        return [
            'wiki_id' => $this->$wiki->id,
            'pages' => $this->todayPageCount,
            'is_deleted' => $this->$isDeleted,
            'daily_actions'=> $this->$dailyActions,
            'weekly_actions'=> $this->$weeklyActions,
            'monthly_actions'=> $this->$monthlyActions,
            'quarterly_actions'=> $this->$quarterlyActions,
        ];
    }

    public function sameAs(WikiMetrics $metrics): bool
    {
        $dataA = $this->getData();
        $dataB = $metrics->getData();

        return $dataA === $dataB;
    }

    public function saveMetrics(Wiki $wiki): void
    {
        $this->wiki = $wiki;

        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;

        $dailyActions = $this->getNumberOfActions(self::INTERVAL_DAILY);
        $weeklyActions = $this->getNumberOfActions(self::INTERVAL_WEEKLY);
        $monthlyActions = $this->getNumberOfActions(self::INTERVAL_MONTHLY);
        $quarterlyActions = $this->getNumberOfActions(self::INTERVAL_QUARTERLY);

        // compare current record to old record and only save if there is a change
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for WikiMetrics ID {$wiki->id}.");
                return;
            }
            if (!$isDeleted) {
                if ($this->sameAs($oldRecord)) {
                    \Log::info("Data unchanged for WikiMetrics ID {$wiki->id}, no new record added.");
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
            'daily_actions'=> $dailyActions,
            'weekly_actions'=> $weeklyActions,
            'monthly_actions'=> $monthlyActions,
            'quarterly_actions'=> $quarterlyActions,
        ]);
        \Log::info("New metric recorded for Wiki ID {$wiki->id}");
    }

    protected function getNumberOfActions($interval): mixed
    {
        $actions = null;

        $wikiDb = Wiki::with('wikiDb')->where('id', $this->wiki->id)->first()->wikiDb;
        $tableRecentChanges = $wikiDb->name . '.' . $wikiDb->prefix . '_recentchanges';
        $tableActor = $wikiDb->name . '.' . $wikiDb->prefix . '_actor';

        $db = app()->db;
        $result = DB::select(self::QUERY_NUMBER_OF_ACTIONS, [
            $interval,
            $tableRecentChanges,
            $tableActor,
        ]);

        $actions = $result->sum_actions;

        return $actions;
    }
}
