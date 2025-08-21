<?php

namespace App\Metrics\App;

use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDailyMetrics;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikiMetrics {
    const INTERVAL_DAILY = 'INTERVAL 1 DAY';

    const INTERVAL_WEEKLY = ' INTERVAL 1 WEEK';

    const INTERVAL_MONTHLY = 'INTERVAL 1 MONTH';

    const INTERVAL_QUARTERLY = 'INTERVAL 3 MONTH';

    protected $wiki;

    public function saveMetrics(Wiki $wiki): void {
        $this->wiki = $wiki;

        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $tripleCount = $this->getNumOfTriples();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool) $wiki->deleted_at;

        $dailyActions = $this->getNumberOfActions(self::INTERVAL_DAILY);
        $weeklyActions = $this->getNumberOfActions(self::INTERVAL_WEEKLY);
        $monthlyActions = $this->getNumberOfActions(self::INTERVAL_MONTHLY);
        $quarterlyActions = $this->getNumberOfActions(self::INTERVAL_QUARTERLY);

        $monthlyNumberOfUsersPerActivityType = $this->getNumberOfUsersPerActivityType();

        $dailyMetrics = new WikiDailyMetrics([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
            'number_of_triples' => $tripleCount,
            'daily_actions' => $dailyActions,
            'weekly_actions' => $weeklyActions,
            'monthly_actions' => $monthlyActions,
            'quarterly_actions' => $quarterlyActions,
            'monthly_casual_users' => $monthlyNumberOfUsersPerActivityType[0],
            'monthly_active_users' => $monthlyNumberOfUsersPerActivityType[1],
        ]);

        // compare current record to old record and only save if there is a change
        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                Log::info("Wiki is deleted, no new record for Wiki ID {$wiki->id}.");

                return;
            }
            if (! $isDeleted) {
                if ($oldRecord->areMetricsEqual($dailyMetrics)) {
                    Log::info("Record unchanged for Wiki ID {$wiki->id}, no new record added.");

                    return;
                }
            }
        }

        $dailyMetrics->save();

        Log::info("New metric recorded for Wiki ID {$wiki->id}");
    }

    protected function getNumOfTriples(): ?int {
        $qsNamespace = QueryserviceNamespace::whereWikiId($this->wiki->id)->first();

        if (! $qsNamespace) {
            Log::info(new \RuntimeException("Namespace for wiki {$this->wiki->id} not found."));

            return null;
        }

        $endpoint = $qsNamespace->backend . '/bigdata/namespace/' . $qsNamespace->namespace . '/sparql';
        $query = 'SELECT (COUNT(*) AS ?triples) WHERE { ?s ?p ?o }';

        $response = Http::withHeaders([
            'Accept' => 'application/sparql-results+json',
        ])->get($endpoint, [
            'query' => $query,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return $data['results']['bindings'][0]['triples']['value'];
        }

        return null;
    }

    protected function getNumberOfActions(string $interval): ?int {
        $actions = null;

        // safeguard
        if (in_array($interval,
            [
                self::INTERVAL_DAILY,
                self::INTERVAL_WEEKLY,
                self::INTERVAL_MONTHLY,
                self::INTERVAL_QUARTERLY,
            ]
        ) === false) {
            return null;
        }

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

    private function getNumberOfUsersPerActivityType(): array {
        $wikiDb = $this->wiki->wikiDb;
        $tableRecentChanges = $wikiDb->name . '.' . $wikiDb->prefix . '_recentchanges';
        $tableActor = $wikiDb->name . '.' . $wikiDb->prefix . '_actor';
        $query = "SELECT
            COUNT(CASE WHEN activity_count >= 1 AND activity_count < 5 THEN 1 END) AS monthly_casual_users,
            COUNT(CASE WHEN activity_count >= 5 THEN 1 END) AS monthly_active_users
        FROM (
            SELECT
                rc.rc_actor,
                COUNT(*) AS activity_count
            FROM
                $tableRecentChanges AS rc
                INNER JOIN $tableActor AS a ON rc.rc_actor = a.actor_id
	        WHERE rc.rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y%m%d%H%i%S') -- monthly
		        /*
		        Conditions below added for consistency with Wikidata: https://phabricator.wikimedia.org/diffusion/ADES/browse/master/src/wikidata/site_stats/sql/active_user_changes.sql
		        */
    	       AND a.actor_user != 0
    	       AND rc.rc_bot = 0
    	       AND (rc.rc_log_type != 'newusers' OR rc.rc_log_type IS NULL)
               GROUP BY rc.rc_actor
        ) AS actor_activity";

        $manager = app()->db;
        $manager->purge('mw');
        $conn = $manager->connection('mw');
        $pdo = $conn->getPdo();
        $result = $pdo->query($query)->fetch();

        return [
            Arr::get($result, 'monthly_casual_users', null),
            Arr::get($result, 'monthly_active_users', null),
        ];
    }
}
