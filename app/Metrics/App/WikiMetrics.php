<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDailyMetrics;
use Illuminate\Support\Arr;

class WikiMetrics
{
    const INTERVAL_DAILY = 'INTERVAL 1 DAY';
    const INTERVAL_WEEKLY = ' INTERVAL 1 WEEK';
    const INTERVAL_MONTHLY = 'INTERVAL 1 MONTH';
    const INTERVAL_QUARTERLY = 'INTERVAL 3 MONTH';

    protected $wiki;

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

        $dailyMetrics = new WikiDailyMetrics([
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

        // compare current record to old record and only save if there is a change
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for WikiMetrics ID {$wiki->id}.");
                return;
            }
            if (!$isDeleted) {
                if ($oldRecord->areMetricsEqual($dailyMetrics)) {
                    \Log::info("Record unchanged for WikiMetrics ID {$wiki->id}, no new record added.");
                    return;
                }
            }
        }

        $dailyMetrics->save();

        \Log::info("New metric recorded for Wiki ID {$wiki->id}");
    }

    protected function getNumberOfActions(string $interval): null|int
    {
        $actions = null;

        // safeguard
        if (false === in_array($interval, 
        [
                self::INTERVAL_DAILY,
                self::INTERVAL_WEEKLY,
                self::INTERVAL_MONTHLY,
                self::INTERVAL_QUARTERLY
                ]
        )) { return null; }

        $wikiDb = $this->wiki->wikiDb;
        $tableRecentChanges = $wikiDb->name . '.' . $wikiDb->prefix . '_recentchanges';
        $tableActor = $wikiDb->name . '.' . $wikiDb->prefix . '_actor';

        $query = "SELECT
            SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), $interval), '%Y%m%d%H%i%S')) AS sum_actions
        FROM
            $tableRecentChanges AS rc
        INNER JOIN $tableActor AS a ON rc.rc_actor = a.actor_id
        WHERE
        /*
        Conditions below added for consistency with Wikidata: https://phabricator.wikimedia.org/diffusion/ADES/browse/master/src/wikidata/site_stats/sql/active_user_changes.sql
        */
        a.actor_user != 0
        AND rc.rc_bot = 0
        AND ( rc.rc_log_type != 'newusers' OR rc.rc_log_type IS NULL)";

        $manager = app()->db;
        $manager->purge('mw');
        $conn = $manager->connection('mw');
        $pdo = $conn->getPdo();
        $result = $pdo->query($query)->fetch();

        $actions = Arr::get($result, 'sum_actions', null);

        return $actions;
    }
}
