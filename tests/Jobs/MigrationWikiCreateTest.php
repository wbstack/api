<?php

namespace Tests\Jobs;

use App\Jobs\MigrationWikiCreate;
use App\QueryserviceNamespace;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Queue\Job;
use Tests\TestCase;

class MigrationWikiCreateTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
    }

    public function testMigrationWithoutUserRuns() {
        $wikiDetailsFilepath = __DIR__.'/../data/example-wiki-details.json';
        $wikiDetails = json_decode(file_get_contents($wikiDetailsFilepath));

        WikiDomain::create(['domain' => $wikiDetails->domain]);
        QueryserviceNamespace::create([
            'namespace' => "fakeNamespaceForTest",
            'backend' => "fakeBackendForTest",
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $manager = $this->app->make('db');

        $job = new MigrationWikiCreate('me@you.com', $wikiDetailsFilepath);
        $job->setJob( $mockJob );
        $job->handle($manager);
    }


    // note: I tried to split this test into several test cases,
    // but it seems like the DB transactions get rolled back after every test case?
    public function testMigrationWikiCreateRunsWithoutFailure()
    {
        $wikiDetailsFilepath = __DIR__.'/../data/example-wiki-details.json';
        $wikiDetails = json_decode(file_get_contents($wikiDetailsFilepath));

        $user = User::factory()->create([
            'verified' => true,
            'id' => 9001,
            'email' => 'foobar@example.com',
        ]);

        WikiDomain::create(['domain' => $wikiDetails->domain]);
        QueryserviceNamespace::create([
            'namespace' => "fakeNamespaceForTest",
            'backend' => "fakeBackendForTest",
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $manager = $this->app->make('db');

        $job = new MigrationWikiCreate($user->email, $wikiDetailsFilepath);
        $job->setJob( $mockJob );
        $job->handle($manager);

        $wikiCollection = Wiki::whereDomain($wikiDetails->domain)->get();
        $wiki = $wikiCollection->first();

        $ownerAssignment = WikiManager::firstWhere([
            'user_id' => $user->id,
            'wiki_id' => $wiki->id,
        ]);

        // This will create a laravel Wiki object and persist it
        $this->assertTrue(
            $wikiCollection->count() === 1
        );

        // This Wiki object will be owned by a user that has the email address
        $this->assertTrue(
            $ownerAssignment->user_id === $user->id
        );

        // A WikiDb will exist; it will have no tables but it will have grants for a) the wiki manager user and b) a specific user for this db
        $wikiDb = WikiDb::firstWhere(['wiki_id' => $wiki->id]);
        $this->assertModelExists($wikiDb);

        $conn = $manager->connection('mw');
        $pdo = $conn->getPdo();
        $queryResult = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$wikiDb->name'");

        $this->assertSame(1, $queryResult->rowCount());

        // The wiki settings will be attached to the Wiki
        $wikiSettings = WikiSetting::where(['wiki_id' => $wiki->id]);
        $this->assertSame(3, $wikiSettings->count());

        // A queryservice namespace will be assigned to this Wiki
        // TODO
    }
}
