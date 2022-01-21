<?php

namespace Tests\Jobs;

use App\Jobs\MigrationWikiCreate;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\WikiManager;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;

class MigrationWikiCreateTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
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

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $manager = $this->app->make('db');

        $job = new MigrationWikiCreate($user->email, $wikiDetailsFilepath);
        $job->setJob( $mockJob );
        $job->handle($manager);

        $wikiCollection = Wiki::whereDomain($wikiDetails->domain)->get();
        $wiki = $wikiCollection->first();

        $ownerAssignment = WikiManager::where([
            'user_id' => $user->id,
            'wiki_id' => $wiki->id,
        ])->first();

        // This will create a laravel Wiki object and persist it
        $this->assertTrue(
            $wikiCollection->count() === 1
        );

        // This Wiki object will be owned by a user that has the email address
        $this->assertTrue(
            $ownerAssignment->user_id === $user->id
        );

        // A WikiDb will exist; it will have no tables but it will have grants for a) the wiki manager user and b) a specific user for this db
        // TODO

        // The wiki settings will be attached to the Wiki
        // TODO

        // A queryservice namespace will be assigned to this Wiki
        // TODO
    }
}
