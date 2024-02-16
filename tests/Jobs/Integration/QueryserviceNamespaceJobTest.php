<?php

namespace Tests\Jobs\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\QueryserviceNamespace;
use App\Jobs\DeleteQueryserviceNamespaceJob;
use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * This is only meant to run when services is started with 
 * additional services from docker-compose.integration.yml
 * 
 * Example: docker-compose exec -e RUN_PHPUNIT_INTEGRATION_TEST=1 -T api vendor/bin/phpunit tests/Jobs/Integration/QueryserviceNamespaceJobTest.php
 */
class QueryserviceNamespaceJobTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;

    public function setUp(): void {
        parent::setUp();
        if ( !getenv('RUN_PHPUNIT_INTEGRATION_TEST') ) {
            $this->markTestSkipped('No blazegraph instance to connect to');
        }  
    }

    public function testIntegrationCreate()
    {
        // both jobs should pass
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never()) ->method('fail');

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->subDays(30)->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $namespace = 'testnamespace';
        $job = new ProvisionQueryserviceNamespaceJob($namespace);
        $job->setJob($mockJob);
        $this->dispatchSync($job);

        DB::table('queryservice_namespaces')->where(['namespace'=>$namespace])->limit(1)->update(['wiki_id' => $wiki->id]);

        $this->assertNotNull(
            QueryserviceNamespace::whereWikiId($wiki->id)->first()
        );

        $job = new DeleteQueryserviceNamespaceJob($wiki->id);
        $job->setJob($mockJob);
        $this->dispatchSync($job);

        $this->assertNull(
            QueryserviceNamespace::whereWikiId($wiki->id)->first()
        );
    }

}