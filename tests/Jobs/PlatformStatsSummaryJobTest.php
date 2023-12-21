<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);
        
        $wikis = [
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki1.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki2.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->subDays(90)->timestamp, 'domain' => 'wiki3.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki4.com' ] )
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
        }
        $stats = [
            [   // inactive
                "wiki" => "wiki1.com",
                "edits" => NULL,
                "pages" => NULL,
                "users" => NULL,
                "active_users" => NULL,
                "lastEdit" => Carbon::now()->subDays(90)->timestamp,
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

            [   // active
                "wiki" => "wiki4.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "active_users" => 1,
                "lastEdit" => Carbon::now()->timestamp,
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],

            [   // deleted
                "wiki" => "wiki3.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "active_users" => 0,
                "lastEdit" => Carbon::now()->timestamp,
                "first100UsingOauth" => "0",
                "platform_summary_version" => "v1"
            ],
        ];
          

       $groups =  $job->prepareStats($stats, $wikis);
    
       $this->assertEquals(
            [
                "total" => 4,
                "deleted" => 1,
                "active" => 1,
                "inactive" => 1,
                "empty" => 1,
                "total_non_deleted_users" => 3,
                "total_non_deleted_active_users" => 1,
                "total_non_deleted_pages" => 2,
                "total_non_deleted_edits" => 1,
                "platform_summary_version" => "v1"
            ],
            $groups, 
        );
    }
    function testCreationStats() {
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
