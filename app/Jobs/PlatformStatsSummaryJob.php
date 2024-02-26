<?php

namespace App\Jobs;

use App\Wiki;
use App\User;
use Illuminate\Database\DatabaseManager;
use PDO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

/*
*
*   Job that extracts platform stats from the api
*
* - active Wikibases (at least one edit in the last 90 days)
* - inactive Wikibases (none of other three categories)
* - deleted wikibases
* - empty wikibases (non deleted wikis with no items, properties, nor pages)
*
*  TODO The stats from wiki statistics doesn't add up https://github.com/wbstack/mediawiki/issues/59
*  We need to fix that before we can get total pages/edits/users
*
* Example: php artisan job:dispatch PlatformStatsSummaryJob
*/
class PlatformStatsSummaryJob extends Job
{
    private $inactiveThreshold;
    private $creationRateRanges;

    private $platformSummaryStatsVersion = "v1";
    public function __construct() {
        $this->inactiveThreshold = Config::get('wbstack.platform_summary_inactive_threshold');
        $this->creationRateRanges = Config::get('wbstack.platform_summary_creation_rate_ranges');
    }

    private function isNullOrEmpty( $value ): bool {
        return is_null($value) || intVal($value) === 0;
    }

    public function getCreationStats(): array {
        $result = [];
        $now = Carbon::now();
        foreach ($this->creationRateRanges as $range) {
            $limit = $now->clone()->sub(new \DateInterval($range));
            $wikis = Wiki::where('created_at', '>=', $limit)->count();
            $result['wikis_created_'.$range] = $wikis;
            $users = User::where('created_at', '>=', $limit)->count();
            $result['users_created_'.$range] = $users;
        }
        return $result;
    }

    public function prepareStats( array $allStats, $wikis): array {

        $deletedWikis = [];
        $activeWikis = [];
        $inactive = [];
        $emptyWikis = [];
        $nonDeletedStats = [];

        $currentTime = Carbon::now()->timestamp;

        foreach( $wikis as $wiki ) {

            if( !is_null($wiki->deleted_at) ) {
                $deletedWikis[] = $wiki;
                continue;
            }

            $wikiDb = $wiki->wikiDb()->first();

            if( !$wikiDb ) {
                Log::error(__METHOD__ . ": Could not find WikiDB for {$wiki->domain}");
                continue;
            }

            $found_key = array_search($wiki->domain, array_column($allStats, 'wiki'));

            if($found_key === false) {
                Log::warning(__METHOD__ . ": Could not find stats for {$wiki->domain}");
                continue;
            }

            $stats = $allStats[$found_key];

            // is it empty?
            if( $this->isNullOrEmpty($stats['edits']) && $this->isNullOrEmpty($stats['pages']) && $this->isNullOrEmpty($stats['lastEdit'])) {
                $emptyWikis[] = $wiki;
                continue;
            }

            $nonDeletedStats[] = $stats;

            // is it just inactive?
            if(!is_null($stats['lastEdit'])){
                $lastTimestamp = intVal($stats['lastEdit']);
                $diff = $currentTime - $lastTimestamp;

                if ($diff >= $this->inactiveThreshold) {
                    $inactive[] = $wiki;
                    continue;
                }
            }

            $activeWikis[] = $wiki;
        }

        $totalNonDeletedUsers = array_sum(array_column($nonDeletedStats, 'users'));
        $totalNonDeletedActiveUsers = array_sum(array_column($nonDeletedStats, 'active_users'));
        $totalNonDeletedPages = array_sum(array_column($nonDeletedStats, 'pages'));
        $totalNonDeletedEdits = array_sum(array_column($nonDeletedStats, 'edits'));

        return [
            'platform_summary_version' => $this->platformSummaryStatsVersion,
            'total' => count($wikis),
            'deleted' => count($deletedWikis),
            'active' => count($activeWikis),
            'inactive' => count($inactive),
            'empty' => count($emptyWikis),
            'total_non_deleted_users' => $totalNonDeletedUsers,
            'total_non_deleted_active_users' => $totalNonDeletedActiveUsers,
            'total_non_deleted_pages' => $totalNonDeletedPages,
            'total_non_deleted_edits' => $totalNonDeletedEdits,
        ];
    }

    public function handle( DatabaseManager $manager ): void
    {
        $wikis = Wiki::withTrashed()->with('wikidb')->get();

        $conn = $manager->connection('mysql');
        $mwConn = $manager->connection('mw');

        if (!$conn instanceof \Illuminate\Database\Connection || !$mwConn instanceof \Illuminate\Database\Connection ) {
           throw new \RuntimeException('Must be run on a PDO based DB connection');
        }

        $pdo = $conn->getPdo();
        $mediawikiPdo = $mwConn->getPdo();

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

        // prepare the first query
        $statement = $pdo->prepare($this->wikiStatsQuery);
        $statement->execute();

        // produces the stats query
        $result = $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        $query = array_values($result)[0];

        // use mw PDO to talk to mediawiki dbs
        $allStats = $mediawikiPdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $summary = $this->prepareStats( $allStats, $wikis );

        $creationStats = $this->getCreationStats();
        $summary = array_merge($summary, $creationStats);

        // Output to be scraped from logs
        if( !App::runningUnitTests() ) {
            print( json_encode($summary) . PHP_EOL );
        }

    }

    private $wikiStatsQuery = <<<EOD
SELECT GROUP_CONCAT(CONCAT(

"SELECT *
FROM
    (
SELECT '",wiki_dbs.wiki_id,"' as wiki
) t1,
    (
SELECT MAX(ss_total_edits) as edits, MAX(ss_total_pages) as pages, MAX(ss_users) as users, MAX(ss_active_users) as active_users
FROM ",wiki_dbs.name,".",wiki_dbs.prefix,"_site_stats
) t2,
    (
SELECT MAX(rev_timestamp) as lastEdit
FROM ",wiki_dbs.name,".",wiki_dbs.prefix,"_revision
) t3,
    (
SELECT COUNT(*) as first100UsingOauth
FROM ",wiki_dbs.name,".",wiki_dbs.prefix,"_change_tag
WHERE ct_rev_id < 100
) t4,
    (
SELECT '",wikis.domain,"' as wiki
) t5
"

) SEPARATOR ' UNION ALL ')

    FROM apidb.wiki_dbs
    LEFT JOIN apidb.wikis ON wiki_dbs.wiki_id = wikis.id;

EOD;

}
