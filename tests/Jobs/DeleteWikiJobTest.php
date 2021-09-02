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

class DeleteWikiJobTest extends TestCase
{
    use DatabaseTransactions;

    public function tearDown(): void {
        parent::setUp();
        $conn = DB::connection('mw');
        $pdo = $conn->getPdo();
        $pdo->exec('DROP DATABASE IF EXISTS the_test_database');
        $pdo->exec('DROP DATABASE IF EXISTS deleted_the_test_database');
    }

    public function testDeletesWiki()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $prefix = 'great_job';
        $databaseName = 'the_test_database';
        $expectedDeletedName = 'deleted_' . $databaseName;
        $job = new ProvisionWikiDbJob('great_job', $databaseName, null);
        $job->handle();

        WikiDb::where([
            'name' => $databaseName,
            'prefix' => $prefix,
        ])->first()->update(['wiki_id' => $wiki->id]);

        $this->assertNotNull( WikiDb::where([ 'wiki_id' => $wiki->id ])->first() );

        $conn = DB::connection('mw');
        $sm = $conn->getDoctrineSchemaManager();
        $initialTables = $sm->listTableNames();
        $pdo = $conn->getPdo();

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new DeleteWikiDbJob( $wiki->id );
        $job->setJob($mockJob);
        $job->handle();

        $databases = $sm->listDatabases();

        $this->assertNull( WikiDb::where([ 'wiki_id' => $wiki->id ])->first() );

        // Both databases now exist, nothing has been dropped
        $this->assertContains( $expectedDeletedName, $databases);

        $this->assertCount(85, $initialTables);
        $this->assertCount(0, $sm->listTableNames());

        // Tables no longer exist in the old one
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'the_test_database.great_job_interwiki' doesn't exist");
        $result = $pdo->exec(sprintf('SELECT * FROM %s.%s_interwiki', $databaseName, $prefix));

        // Tables now live in the deleted database
        $result = $pdo->exec(sprintf('SELECT * FROM %s.%s_interwiki', $expectedDeletedName, $prefix));
        $this->assertSame(0, $result);

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

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
                ->method('fail')
                ->with(new \RuntimeException(str_replace('<WIKI_ID>', $wiki_id, $expectedFailure)));
                
        $job = new DeleteWikiDbJob($wiki_id);
        $job->setJob($mockJob);
        $job->handle();
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
