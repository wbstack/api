<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Jobs\DeleteWikiDbJob;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiDb;
use App\Jobs\ProvisionWikiDbJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\Job;
use Carbon\Carbon;
use PDOException;
use PHPUnit\TextUI\RuntimeException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Database\DatabaseManager;

class DeleteWikiJobTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;

    private $wiki;
    protected $connectionsToTransact = ['mysql', 'mw'];

    protected function setUp(): void {
        parent::setUp();
        DB::delete( "DELETE FROM wiki_dbs WHERE name='the_test_database';" );
        DB::delete( "DELETE FROM wiki_dbs WHERE name='the_test_database_not_to_be_deleted';" );
        DB::connection('mysql')->getPdo()->exec('DROP DATABASE IF EXISTS the_test_database; DROP DATABASE IF EXISTS the_test_database_not_to_be_deleted');
    }

    private function getExpectedDeletedDatabaseName( $wiki ): string {
        return "mwdb_deleted_1631534400_" . $wiki->id;
    }

    private function getResultValues( $resultRows ) {
        $results = [];
        foreach($resultRows as $row) {
            $results[] = array_unique(array_values($row))[0];
        }

        return $results;
    }

    public function testDispatching() {
        $mockJob = $this->createMock(Job::class);
        $job = new DeleteWikiDbJob(-1);
        $job->setJob($mockJob);
        $mockJob->expects($this->once())
            ->method('fail');
        $this->dispatchNow($job);
    }

    public function testDeletesWiki()
    {
        Carbon::setTestNow(Carbon::create(2021, 9, 13, 12));

        $user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $user->id]);

        $databaseName = 'the_test_database';
        $expectedDeletedName = $this->getExpectedDeletedDatabaseName( $this->wiki );
        $databases = [
            [
                "prefix" => "prefix",
                "name" => "the_test_database"
            ],
            [
                "prefix" => "prefix2",
                "name" => "the_test_database_not_to_be_deleted"
            ]
        ];

        // Would be injected by the app
        $manager = $this->app->make('db');
  
        $job = new ProvisionWikiDbJob($databases[0]['prefix'], $databases[0]['name'], null);
        $job->handle($manager);

        // Would be injected by the app
        $manager = $this->app->make('db');
  
        $job = new ProvisionWikiDbJob($databases[1]['prefix'], $databases[1]['name'], null);
        $job->handle($manager);

        $databaseName = 'the_test_database';

        // set the first database to be this wikis database
        WikiDb::where([
            'name' => $databaseName,
        ])->first()->update(['wiki_id' => $this->wiki->id]);

        // make sure it stuck
        $wikiDB = WikiDb::where([ 'wiki_id' => $this->wiki->id ])->first();
        $this->assertNotNull( $wikiDB );

        // get a new connection and look at the tables for later assertions
        $conn = $this->app->make('db')->connection('mw');
        $pdo = $conn->getPdo();
        $pdo->exec("USE {$wikiDB->name}");
        $initialTables = $pdo->query("SHOW TABLES")->fetchAll();
        $conn->disconnect();

        // we now have some mediawiki tables here
        $this->assertCount(86, $initialTables);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        // would get injected by the app
        $manager = $this->app->make('db');

        // this job will kill the underlying connection
        $job = new DeleteWikiDbJob( $this->wiki->id );
        $job->setJob($mockJob);
        $job->handle($manager);

        // get a new connection and take a look at the database tables and newly created databases
        $conn = $this->app->make('db')->connection('mw');
        $pdo = $conn->getPdo();
        $pdo->exec("USE {$wikiDB->name}");
        $databases = $this->getResultValues($pdo->query("SHOW DATABASES")->fetchAll());

        $this->assertNull( WikiDb::where([ 'wiki_id' => $this->wiki->id ])->first() );

        // Both databases now exist, nothing has been dropped
        $this->assertContains( $expectedDeletedName, $databases);

        // after delete job we don't have any tables here any more
        $this->assertCount(0, $this->getResultValues( $pdo->query("SHOW TABLES")->fetchAll()));

        // all tables are now in the new deleted database
        $pdo->exec("USE {$expectedDeletedName}");
        $this->assertCount(86, $this->getResultValues( $pdo->query("SHOW TABLES")->fetchAll()));

        // Content now live in the deleted database
        $result = $pdo->query(sprintf('SELECT * FROM %s.interwiki', $expectedDeletedName))->fetchAll();
        $this->assertCount(66, $result);

        // cleanup test deleted database
        $pdo->exec("DROP DATABASE {$this->getExpectedDeletedDatabaseName( $this->wiki )}");

        // Tables no longer exist in the old one
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'the_test_database.prefix_interwiki' doesn't exist");
        $pdo->exec(sprintf('SELECT * FROM %s.%s_interwiki', $databaseName, $wikiDB->prefix ));
    }

    /**
	 * @dataProvider failureProvider
	 */
    public function testFailure( $wiki_id, $deleted_at, string $expectedFailure)
    {
        if ($wiki_id !== -1) {
            $wiki = Wiki::factory()->create( [  'deleted_at' => $deleted_at ] );
            $wiki_id = $wiki->id;
        }

        $mockMananger = $this->createMock(DatabaseManager::class);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
                ->method('fail')
                ->with(new \RuntimeException(str_replace('<WIKI_ID>', $wiki_id, $expectedFailure)));
                
        $job = new DeleteWikiDbJob($wiki_id);
        $job->setJob($mockJob);
        $job->handle($mockMananger);
    }

    public function failureProvider() {

        yield [
            -1,
            null,
            'Wiki not found for <WIKI_ID>',
        ];

        yield [
            1,
            null,
            'Wiki <WIKI_ID> is not marked for deletion.',
        ];

        yield [
            1,
            Carbon::now()->subDays(30)->timestamp,
            'WikiDb not found for <WIKI_ID>',
        ];
    }
}
