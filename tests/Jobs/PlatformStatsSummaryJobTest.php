<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
use Illuminate\Support\Facades\Notification;
use App\Notifications\PlatformStatsSummaryNotification;

class PlatformStatsSummaryJobTest extends TestCase
{
    use DatabaseTransactions;

    private $numWikis = 5;
    private $wikis = [];

    private $db_prefix = "somecoolprefix";
    private $db_name = "some_cool_db_name";

    protected function setUp(): void {
        parent::setUp();
        for($n = 0; $n < $this->numWikis; $n++ ) {
            DB::connection('mysql')->getPdo()->exec("DROP DATABASE IF EXISTS {$this->db_name}{$n};");
        }
        $this->seedWikis();
        $this->manager = $this->app->make('db');
    }

    protected function tearDown(): void {
        foreach($this->wikis as $wiki) {
            $wiki['wiki']->wikiDb()->forceDelete();
            $wiki['wiki']->forceDelete();
        }
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

            $this->wikis[] = [
                'user' => $user,
                'wiki' => Wiki::whereId($wiki->id)->with('wikidb')->first()
            ];
        }

    }
    public function testQueryGetsStats()
    {
        Notification::fake();

        $manager = $this->app->make('db');

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);

        $job->handle($manager);

        Notification::assertSentOnDemandTimes(
            PlatformStatsSummaryNotification::class,
            1
        );
    }

    public function testGroupings()
    {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new PlatformStatsSummaryJob();
        $job->setJob($mockJob);
        
        $testWikis = [
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki1.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki2.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->subDays(90)->timestamp, 'domain' => 'wiki3.com' ] ),
            Wiki::factory()->create( [ 'deleted_at' => null, 'domain' => 'wiki4.com' ] )

        ];

        foreach($testWikis as $wiki) {
            $wikiDB = WikiDb::create([
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
                "lastEdit" => Carbon::now()->subDays(90)->timestamp,
                "first100UsingOauth" => "0"
            ],
            [   // empty
                "wiki" => "wiki2.com",
                "edits" => NULL,
                "pages" => NULL,
                "users" => NULL,
                "lastEdit" => NULL,
                "first100UsingOauth" => "0"
            ],

            [   // deleted
                "wiki" => "wiki4.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "lastEdit" => Carbon::now()->timestamp,
                "first100UsingOauth" => "0"
            ],

            [   // active
                "wiki" => "wiki3.com",
                "edits" => 1,
                "pages" => 2,
                "users" => 3,
                "lastEdit" => Carbon::now()->timestamp,
                "first100UsingOauth" => "0"
            ]
        ];
          

       $groups =  $job->prepareStats($stats, $testWikis);
    
       $this->assertEquals(
            [
                "total" => 4,
                "deleted" => 1,
                "active" => 1,
                "inactive" => 1,
                "empty" => 1,
                "total_non_deleted_users" => 3,
                "total_non_deleted_pages" => 2,
                "total_non_deleted_edits" => 1,
            ],
            $groups, 
        );
    }


}
