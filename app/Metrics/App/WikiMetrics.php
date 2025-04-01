<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiDb;
use Illuminate\Database\DatabaseManager;
use PDO;

class WikiMetrics
{
    public function saveMetrics(Wiki $wiki, DatabaseManager $manager): void
    {
        $dailyActions = null;
        $weeklyActions = null;
        $monthlyActions = null;
        $quarterlyActions = null;
        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $number_of_actions = $this->getNumberOfActions($wiki, $manager);
        if (array_key_exists('daily_actions', $number_of_actions)) {
            $dailyActions = $number_of_actions['daily_actions'];
            $weeklyActions = $number_of_actions['weekly_actions'];
            $monthlyActions = $number_of_actions['monthly_actions'];
            $quarterlyActions = $number_of_actions['quarterly_actions'];
        }
        $isDeleted = (bool)$wiki->deleted_at;

        // compare current record to old record and only save if there is a change
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for WikiMetrics ID {$wiki->id}.");
                return;
            }
            if (!$isDeleted) {
                if (
                    $oldRecord->pages === $todayPageCount /*&&
                    $oldRecord->daily_actions === $dailyActions &&
                    $oldRecord->weekly_actions === $weeklyActions &&
                    $oldRecord->monthly_actions === $monthlyActions &&
                    $oldRecord->quarterly_actions === $quarterlyActions*/
                    )
                {
                    \Log::info("Metrics unchanged for Wiki ID {$wiki->id}, no new record added.");
                    return;
                }
            }
        }

        //save metrics
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

    protected function getNumberOfActions(Wiki $wiki, DatabaseManager $manager) {
        $wikiDb = WikiDb::whereWikiId($wiki->id)->first();
        $result =[];
        if ($wikiDb) {
            $wikiTableRcName = "{$wikiDb->name}.{$wikiDb->prefix}_recentchanges";
            $wikiTableActorName = "{$wikiDb->name}.{$wikiDb->prefix}_actor";
            $manager->purge('mw');
            $conn = $manager->connection('mw');
            if (! $conn instanceof \Illuminate\Database\Connection) {
                $this->fail(new \RuntimeException('Must be run on a PDO based DB connection'));

                return; //safegaurd
            }
            $pdo = $conn->getPdo();
            $pdo->exec("USE {$wikiDb->name}");
            $result = $pdo->query("SELECT
    SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y%m%d%H%i%S')) AS daily_actions,
    SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y%m%d%H%i%S')) AS weekly_actions,
    SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y%m%d%H%i%S')) AS monthly_actions,
    SUM(rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 MONTH), '%Y%m%d%H%i%S')) AS quarterly_actions
FROM
    {$wikiTableRcName} rc
INNER JOIN {$wikiTableActorName} a ON rc.rc_actor = a.actor_id
WHERE a.actor_name <> 'PlatformReservedUser'
/*
Conditions below added for consistency with Wikidata:
https://phabricator.wikimedia.org/diffusion/ADES/browse/master/src/wikidata/site_stats/sql/active_user_changes.sql
*/
    AND a.actor_user != 0
    AND rc.rc_bot = 0
    AND ( rc.rc_log_type != 'newusers' OR rc.rc_log_type IS NULL)")->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}

