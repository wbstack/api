<?php

namespace Tests\Jobs;

use App\Helper\MWTimestampHelper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiDb;
use App\Jobs\ProvisionWikiDbJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\Job;
use Carbon\Carbon;
use App\Jobs\PlatformStatsSummaryJob;

class PlatformStatsSummaryJobTest extends TestCase
{
    use RefreshDatabase;

    private $numWikis = 5;
    private $wikis = [];
    private $users = [];

    private $db_prefix = "somecoolprefix";
    private $db_name = "some_cool_db_name";

    protected function setUp(): void {
        parent::setUp();
        for ($n = 0; $n < $this->numWikis; $n++) {
            DB::connection('mysql')->getPdo()->exec("DROP DATABASE IF EXISTS {$this->db_name}{$n};");
        }
        $this->wikis = [];
        $this->users = [];
    }

    protected function tearDown(): void {
        Wiki::query()->delete();
        User::query()->delete();
        WikiManager::query()->delete();
        WikiDb::query()->delete();
        parent::tearDown();
    }

    private function seedWikis() {
        $manager = $this->app->make('db');
        for($n = 0; $n < $this->numWikis; $n++ ) {

            $user = User::factory()->create(['verified' => true]);
            $wiki = Wiki::factory()->create( [ 'deleted_at' => null ] );
            WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

            $job = new ProvisionWikiDbJob($this->db_prefix . $n, $this->db_name . $n, null);
            $job->handle($manager);

            $wikiDb = WikiDb::whereName($this->db_name.$n)->first();
            $wikiDb->update( ['wiki_id' => $wiki->id] );

            $this->wikis[] = $wiki;
            $this->users[] = $user;
        }

    }
    public function testQueryGetsStats()
    {
        $this->markTestSkipped('Pollutes the deleted wiki list');
        Http::fake();
        $this->seedWikis();
        $manager = $this->app->make('db');

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        $job->handle($manager);
    }

    public function testGroupings()
    {
        $this->markTestSkipped('Pollutes the deleted wiki list');
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        $wikis = [
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki1.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki2.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => CarbonImmutable::now()->subDays(90)->timestamp, 'domain' => 'wiki3.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki4.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki5.com' ] )
        ];

        foreach($wikis as $wiki) {
            WikiDb::create([
                'name' => 'mwdb_asdasfasfasf' . $wiki->id,
                'user' => 'asdasd',
                'password' => 'asdasfasfasf',
                'version' => 'asdasdasdas',
                'prefix' => 'asdasd',
                'wiki_id' => $wiki->id
            ]);
            //Generate some items/properties for testing, each wiki will have 3 props and 9 items
            Http::fake([
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
                    'query' => [
                        'allpages' => [
                            ['title' => 'Property:P1', 'namespace' => 122],
                            ['title' => 'Property:P9', 'namespace' => 122],
                            ['title' => 'Property:P11', 'namespace' => 122],
                        ],
                    ],
                ], 200),
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
                    'continue' => [
                        'apcontinue' => 'Q6',
                    ],
                    'query' => [
                        'allpages' => [
                            ['title' => 'Item:Q1', 'namespace' => 120],
                            ['title' => 'Item:Q2', 'namespace' => 120],
                            ['title' => 'Item:Q3', 'namespace' => 120],
                            ['title' => 'Item:Q4', 'namespace' => 120],
                            ['title' => 'Item:Q5', 'namespace' => 120],
                        ],
                    ],
                ], 200),
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=Q6&aplimit=max&format=json' => Http::response([
                    'query' => [
                        'allpages' => [
                            ['title' => 'Item:Q6', 'namespace' => 120],
                            ['title' => 'Item:Q7', 'namespace' => 120],
                            ['title' => 'Item:Q8', 'namespace' => 120],
                            ['title' => 'Item:Q9', 'namespace' => 120],
                        ],
                    ]
                ], 200)
            ]);
        }

        $stats = [
            [   // no edits in last 90days but recent enough to have a lastEdit
                "wiki" => "wiki1.com",
                "edits" => 1,
                "pages" => 1,
                "users" => 1,
                "active_users" => NULL,
                "lastEdit" => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()->subDays(100)),
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],
            [   // no edits in last 90 days so old that mediawiki reports no last edit
                "wiki" => "wiki5.com",
                "edits" => 1,
                "pages" => 1,
                "users" => 1,
                "active_users" => NULL,
                "lastEdit" => NULL,
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],
            [   // empty
                "wiki" => "wiki2.com",
                "edits" => NULL,
                "pages" => NULL,
                "users" => NULL,
                "active_users" => NULL,
                "lastEdit" => NULL,
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],

            [   // edited within last 90 days
                "wiki" => "wiki4.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "active_users" => 1,
                "lastEdit" => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],

            [   // deleted
                "wiki" => "wiki3.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "active_users" => 0,
                "lastEdit" => MWTimestampHelper::getMWTimestampFromCarbon(CarbonImmutable::now()),
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],
        ];

        $groups =  $job->prepareStats($stats, $wikis);

        $this->assertEquals(
            [
                "total" => 5,
                "deleted" => 1,
                "edited_last_90_days" => 1,
                "not_edited_last_90_days" => 2,
                "empty" => 1,
                "total_non_deleted_users" => 5,
                "total_non_deleted_active_users" => 1,
                "total_non_deleted_pages" => 4,
                "total_non_deleted_edits" => 3,
                "platform_summary_version" => "v1",
                "total_items_count" => 4*9, //there are 4 non-deleted wikis and each has 9 items
                "total_properties_count" => 4*3 //there are 4 non-deleted wikis and each has 3 properties
            ],
            $groups,
        );
    }
    function testCreationStats() {
        $this->markTestSkipped('Pollutes the deleted wiki list');
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        Wiki::factory()->create([
            'created_at' => Carbon::now()->subHours(1)
        ]);
        Wiki::factory()->create([
            'created_at' => Carbon::now()->subDays(2)
        ]);
        Wiki::factory()->create([
            'created_at' => Carbon::now()->subDays(90)
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subHours(1)
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subHours(2)
        ]);
        User::factory()->create([
            'created_at' => Carbon::now()->subDays(200)
        ]);

        $stats =  $job->getCreationStats();

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
}
