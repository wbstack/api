<?php

namespace Tests\Jobs;

use App\Jobs\MigrationWikiCreate;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use App\WikiManager;
use App\WikiSetting;
use App\Wiki;
use App\Jobs\ElasticSearchIndexDelete;
use App\WikiDb;
use Illuminate\Contracts\Queue\Job;

class MigrationWikiCreateTest extends TestCase
{
    use DatabaseTransactions;

    private $wiki;
    private $user;
    private $wikiDb;

    public function setUp(): void {
        parent::setUp();
    }

    public function testMigrationWikiCreate()
    {
        $email = 'foobar@example.com';
        $wikiDetailsFilepath = __DIR__.'/../data/example-wiki-details.json';

        $user = User::factory()->create([
            'verified' => true,
            'email' => $email,
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $manager = $this->app->make('db');

        $job = new MigrationWikiCreate($email, $wikiDetailsFilepath);
        $job->setJob( $mockJob );
        $job->handle($manager);
    }
}
