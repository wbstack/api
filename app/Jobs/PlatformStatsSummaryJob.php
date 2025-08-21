<?php

namespace App\Jobs;

use App\Constants\MediawikiNamespace;
use App\Helper\MWTimestampHelper;
use App\Traits;
use App\User;
use App\Wiki;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PDO;

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
class PlatformStatsSummaryJob extends Job {
    use Traits\PageFetcher;

    public $timeout = 3600;

    private $inactiveThreshold;

    private $creationRateRanges;

    private $platformSummaryStatsVersion = 'v1';

    public function __construct() {
        $this->inactiveThreshold = Config::get('wbstack.platform_summary_inactive_threshold');
        $this->creationRateRanges = Config::get('wbstack.platform_summary_creation_rate_ranges');
        $this->apiUrl = getenv('PLATFORM_MW_BACKEND_HOST') . '/w/api.php';
    }

    private function isNullOrEmpty($value): bool {
        return is_null($value) || intval($value) === 0;

    }

    public function getCreationStats(): array {
        $result = [];
        $now = CarbonImmutable::now();
        foreach ($this->creationRateRanges as $range) {
            $limit = $now->clone()->sub(new \DateInterval($range));
            $wikis = Wiki::where('created_at', '>=', $limit)->count();
            $result['wikis_created_' . $range] = $wikis;
            $users = User::where('created_at', '>=', $limit)->count();
            $result['users_created_' . $range] = $users;
        }

        return $result;
    }

    public function prepareStats(array $allStats, $wikis): array {

        $deletedWikis = [];
        $editedLast90DaysWikis = [];
        $notEditedLast90DaysWikis = [];
        $emptyWikis = [];
        $nonDeletedStats = [];
        $itemsCount = [];
        $propertiesCount = [];

        $currentTime = CarbonImmutable::now();

        foreach ($wikis as $wiki) {

            if (! is_null($wiki->deleted_at)) {
                $deletedWikis[] = $wiki;

                continue;
            }

            // add items and properties counts of the wiki to the corresponded arrays
            try {
                $nextItemCount = count($this->fetchPagesInNamespace($wiki->domain, MediawikiNamespace::item));
                array_push($itemsCount, $nextItemCount);
            } catch (\Exception $ex) {
                Log::warning('Failed to fetch item count for wiki ' . $wiki->domain . ', will use 0 instead.');
            }
            try {
                $nextPropertyCount = count($this->fetchPagesInNamespace($wiki->domain, MediawikiNamespace::property));
                array_push($propertiesCount, $nextPropertyCount);
            } catch (\Exception $ex) {
                Log::warning('Failed to fetch property count for wiki ' . $wiki->domain . ', will use 0 instead.');
            }

            $wikiDb = $wiki->wikiDb()->first();

            if (! $wikiDb) {
                Log::error(__METHOD__ . ": Could not find WikiDB for {$wiki->domain}");

                continue;
            }

            $found_key = array_search($wiki->domain, array_column($allStats, 'wiki'));

            if ($found_key === false) {
                Log::warning(__METHOD__ . ": Could not find stats for {$wiki->domain}");

                continue;
            }

            $stats = $allStats[$found_key];

            // is it empty?
            if ($this->isNullOrEmpty($stats['edits']) && $this->isNullOrEmpty($stats['pages']) && $this->isNullOrEmpty($stats['lastEdit'])) {
                $emptyWikis[] = $wiki;

                continue;
            }

            $nonDeletedStats[] = $stats;

            // is it edited in the last 90 days?
            if (! is_null($stats['lastEdit'])) {
                $lastTimestamp = MWTimestampHelper::getCarbonFromMWTimestamp(intval($stats['lastEdit']));
                $diff = $lastTimestamp->diffInSeconds($currentTime);

                if ($diff <= $this->inactiveThreshold) {
                    $editedLast90DaysWikis[] = $wiki;

                    continue;
                }
            }

            // if it's neither deleted, empty or active it must not be edited in the last 90 days
            $notEditedLast90DaysWikis[] = $wiki;
        }

        $totalNonDeletedUsers = array_sum(array_column($nonDeletedStats, 'users'));
        $totalNonDeletedActiveUsers = array_sum(array_column($nonDeletedStats, 'active_users'));
        $totalNonDeletedPages = array_sum(array_column($nonDeletedStats, 'pages'));
        $totalNonDeletedEdits = array_sum(array_column($nonDeletedStats, 'edits'));
        $totalItemsCount = array_sum($itemsCount);
        $totalPropertiesCount = array_sum($propertiesCount);

        return [
            'platform_summary_version' => $this->platformSummaryStatsVersion,
            'total' => count($wikis),
            'deleted' => count($deletedWikis),
            'edited_last_90_days' => count($editedLast90DaysWikis),
            'not_edited_last_90_days' => count($notEditedLast90DaysWikis),
            'empty' => count($emptyWikis),
            'total_non_deleted_users' => $totalNonDeletedUsers,
            'total_non_deleted_active_users' => $totalNonDeletedActiveUsers,
            'total_non_deleted_pages' => $totalNonDeletedPages,
            'total_non_deleted_edits' => $totalNonDeletedEdits,
            'total_items_count' => $totalItemsCount,
            'total_properties_count' => $totalPropertiesCount,
        ];
    }

    public function handle(DatabaseManager $manager): void {
        $wikis = Wiki::withTrashed()->with('wikidb')->get();

        $conn = $manager->connection('mysql');
        $mwConn = $manager->connection('mw');

        if (! $conn instanceof \Illuminate\Database\Connection || ! $mwConn instanceof \Illuminate\Database\Connection) {
            throw new \RuntimeException('Must be run on a PDO based DB connection');
        }

        $pdo = $conn->getPdo();
        $mediawikiPdo = $mwConn->getPdo();

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

        // Prepare a query for the Platform API DB to
        // construct an SQL query we will
        // run to get the actual stats
        $statement = $pdo->prepare($this->wikiStatsQuery);
        $statement->execute();

        // Run the first query to construct the second query
        $result = $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        $query = array_values($result)[0];

        // Execute the second query using the mw
        // PDO to talk to mediawiki dbs
        $allStats = $mediawikiPdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $summary = $this->prepareStats($allStats, $wikis);

        $creationStats = $this->getCreationStats();
        $summary = array_merge($summary, $creationStats);

        // Output to be scraped from logs
        if (! App::runningUnitTests()) {
            echo  json_encode($summary) . PHP_EOL;
        }
    }

    private $wikiStatsQuery = <<<'EOD'
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
