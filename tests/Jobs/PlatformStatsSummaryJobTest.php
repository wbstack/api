<?php

namespace Tests\Jobs;

use App\Helper\MWTimestampHelper;
use App\Jobs\PlatformStatsSummaryJob;
use App\Jobs\ProvisionWikiDbJob;
use App\Services\MediaWikiHostResolver;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformStatsSummaryJobTest extends TestCase {
    use RefreshDatabase;

    private $numWikis = 5;

    private $wikis = [];

    private $users = [];

    private $provisionedWikiDbs = [];

    private $db_prefix = 'somecoolprefix';

    private $db_name = 'some_cool_db_name';

    private $mwBackendHost;

    private $mockMwHostResolver;

    protected function setUp(): void {
        parent::setUp();
        for ($n = 0; $n < $this->numWikis; $n++) {
            DB::connection('mw')->getPdo()->exec("DROP DATABASE IF EXISTS {$this->db_name}{$n};");
        }
        $this->wikis = [];
        $this->users = [];
        $this->provisionedWikiDbs = [];

        $this->mwBackendHost = 'http://mediawiki.localhost';

        $this->mockMwHostResolver = $this->createMock(MediaWikiHostResolver::class);
        $this->mockMwHostResolver->method('getBackendUrlForDomain')->willReturn(
            $this->mwBackendHost
        );
    }

    protected function tearDown(): void {
        $this->cleanupProvisionedWikiDbs();
        parent::tearDown();
    }

    private function cleanupProvisionedWikiDbs(): void {
        $pdo = DB::connection('mw')->getPdo();
        foreach ($this->provisionedWikiDbs as $wikiDb) {
            $pdo->exec("DROP DATABASE IF EXISTS `{$wikiDb->name}`");
            $pdo->exec('DROP USER IF EXISTS ' . $pdo->quote($wikiDb->user) . "@'%'");
        }
    }

    private function seedWikis() {
        $manager = $this->app->make('db');
        for ($n = 0; $n < $this->numWikis; $n++) {

            $user = User::factory()->create(['verified' => true]);
            $wiki = Wiki::factory()->create(['deleted_at' => null]);
            WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

            $job = new ProvisionWikiDbJob($this->db_prefix . $n, $this->db_name . $n, null);
            $job->handle($manager, $this->mockMwHostResolver);

            $wikiDb = WikiDb::whereName($this->db_name . $n)->first();
            $wikiDb->update(['wiki_id' => $wiki->id]);

            $this->wikis[] = $wiki;
            $this->users[] = $user;
            $this->provisionedWikiDbs[] = $wikiDb;
        }

    }

    public function testQueryGetsStats() {
        $this->seedWikis();
        $manager = $this->app->make('db');

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);
        Http::preventStrayRequests();
        $job->handle($manager);
    }

    public function testGroupings() {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        $wikis = [
            Wiki::factory()->create(['deleted_at' => null, 'domain' => 'wiki1.com']),
            Wiki::factory()->create(['deleted_at' => null, 'domain' => 'wiki2.com']),
            Wiki::factory()->create(['deleted_at' => CarbonImmutable::now()->subDays(90)->timestamp, 'domain' => 'wiki3.com']),
            Wiki::factory()->create(['deleted_at' => null, 'domain' => 'wiki4.com']),
            Wiki::factory()->create(['deleted_at' => null, 'domain' => 'wiki5.com']),
        ];

        foreach ($wikis as $wiki) {
            WikiDb::create([
                'name' => 'mwdb_asdasfasfasf' . $wiki->id,
                'user' => 'asdasd',
                'password' => 'asdasfasfasf',
                'version' => 'asdasdasdas',
                'prefix' => 'asdasd',
                'wiki_id' => $wiki->id,
            ]);
        }

        $stats = [
            [   // no edits in last 90days but recent enough to have a lastEdit
                'wiki' => 'wiki1.com',
                'edits' => 1,
                'pages' => 1,
                'users' => 1,
                'active_users' => null,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()->subDays(100)),
                'items' => 9,
                'properties' => 3,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
            [   // no edits in last 90 days so old that mediawiki reports no last edit
                'wiki' => 'wiki5.com',
                'edits' => 1,
                'pages' => 1,
                'users' => 1,
                'active_users' => null,
                'lastEdit' => null,
                'items' => 9,
                'properties' => 3,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
            [   // empty
                'wiki' => 'wiki2.com',
                'edits' => null,
                'pages' => null,
                'users' => null,
                'active_users' => null,
                'lastEdit' => null,
                'items' => 9,
                'properties' => 3,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],

            [   // edited within last 90 days
                'wiki' => 'wiki4.com',
                'edits' => 1,
                'pages' => 2,
                'users' => 3,
                'active_users' => 1,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                'items' => 9,
                'properties' => 3,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],

            [   // deleted
                'wiki' => 'wiki3.com',
                'edits' => 1,
                'pages' => 2,
                'users' => 3,
                'active_users' => 0,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                'items' => 9,
                'properties' => 3,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
        ];

        $groups = $job->prepareStats($stats, $wikis);

        $this->assertEquals(
            [
                'total' => 5,
                'deleted' => 1,
                'edited_last_90_days' => 1,
                'not_edited_last_90_days' => 2,
                'empty' => 1,
                'total_non_deleted_users' => 5,
                'total_non_deleted_active_users' => 1,
                'total_non_deleted_pages' => 4,
                'total_non_deleted_edits' => 3,
                'platform_summary_version' => 'v1',
                'total_items_count' => 4 * 9, // there are 4 non-deleted wikis and each has 9 items
                'total_properties_count' => 4 * 3, // there are 4 non-deleted wikis and each has 3 properties
            ],
            $groups,
        );
    }

    public function testSkipDeletedWikis() {
        $deletedWiki = Wiki::factory()->create(['deleted_at' => CarbonImmutable::yesterday(), 'domain' => 'deleted.cloud']);
        WikiDb::factory()->for($deletedWiki)->create(['name' => 'deleted_db']);

        $activeWiki = Wiki::factory()->create(['deleted_at' => null, 'domain' => 'active.cloud']);
        WikiDb::factory()->for($activeWiki)->create(['name' => 'active_db']);

        $job = new PlatformStatsSummaryJob();

        $groups = $job->prepareStats([
            [
                'wiki' => 'active.cloud',
                'edits' => 1,
                'pages' => 1,
                'users' => 1,
                'active_users' => 1,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                'items' => 0,
                'properties' => 0,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
            [
                'wiki' => 'deleted.cloud',
                'edits' => 1,
                'pages' => 1,
                'users' => 1,
                'active_users' => 1,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                'items' => 0,
                'properties' => 0,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
        ], [$deletedWiki, $activeWiki]);

        $this->assertSame(1, $groups['deleted']);
        $this->assertSame(1, $groups['edited_last_90_days']);
    }

    public function testPrepareStatsDoesNotQueryEagerLoadedWikiDbs(): void {
        $wikiCount = 25;
        $itemsPerWiki = 10;
        $propertiesPerWiki = 3;
        $wikis = Wiki::factory()->count($wikiCount)->create(['deleted_at' => null]);
        foreach ($wikis as $wiki) {
            WikiDb::factory()->for($wiki)->create();
        }

        $wikis = Wiki::with('wikiDb')->get();
        $stats = $wikis->map(fn (Wiki $wiki) => [
            'wiki' => $wiki->domain,
            'edits' => 1,
            'pages' => 1,
            'users' => 1,
            'active_users' => 1,
            'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
            'items' => $itemsPerWiki,
            'properties' => $propertiesPerWiki,
        ])->all();

        $job = new PlatformStatsSummaryJob();
        Http::preventStrayRequests();

        $connection = DB::connection('mysql');
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $groups = $job->prepareStats($stats, $wikis);
        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        $wikiDbQueries = array_filter($queries, fn (array $query) => str_contains($query['query'], 'from `wiki_dbs`'));

        $this->assertSame($wikiCount, $groups['edited_last_90_days']);
        $this->assertSame($wikiCount * $itemsPerWiki, $groups['total_items_count']);
        $this->assertSame($wikiCount * $propertiesPerWiki, $groups['total_properties_count']);
        $this->assertCount(0, $wikiDbQueries);
    }

    public function testCreationStats() {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        Wiki::factory()->create([
            'created_at' => Carbon::now()->subHours(1),
        ]);
        Wiki::factory()->create([
            'created_at' => Carbon::now()->subDays(2),
        ]);
        Wiki::factory()->create([
            'created_at' => Carbon::now()->subDays(90),
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subHours(1),
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subHours(2),
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subDays(200),
        ]);

        $stats = $job->getCreationStats();

        $this->assertEquals(
            [
                'wikis_created_PT24H' => 1,
                'wikis_created_P30D' => 2,
                'users_created_PT24H' => 2,
                'users_created_P30D' => 2,
            ],
            $stats,
        );

    }

    public function testPrepareStatsTreatsSecondPrecisionTimestampAtThresholdAsActive() {
        $currentTime = CarbonImmutable::now();

        $wiki = Wiki::factory()->create(['deleted_at' => null, 'domain' => 'thresholdtest.com']);
        WikiDb::create([
            'name' => 'mwdb_threshold_' . $wiki->id,
            'user' => 'user',
            'password' => 'password',
            'version' => 'version',
            'prefix' => 'prefix',
            'wiki_id' => $wiki->id,
        ]);

        $job = new PlatformStatsSummaryJob();

        $groups = $job->prepareStats([
            [
                'wiki' => 'thresholdtest.com',
                'edits' => 1,
                'pages' => 1,
                'users' => 1,
                'active_users' => 1,
                'lastEdit' => MWTimestampHelper::getMWTimestampFromCarbon(
                    $currentTime->subSeconds(config('wbstack.platform_summary_inactive_threshold'))
                ),
                'items' => 0,
                'properties' => 0,
                'first100UsingOauth' => '0',
                'platform_summary_version' => 'v1',
            ],
        ], [$wiki]);

        $this->assertSame(1, $groups['edited_last_90_days']);
        $this->assertSame(0, $groups['not_edited_last_90_days']);
    }
}
