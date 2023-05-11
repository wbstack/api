<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\PollForMediaWikiJobsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PollForMediaWikiJobsJobTest extends TestCase
{

    use RefreshDatabase;

    private $wiki;

    public function setUp(): void
    {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }

    public function tearDown(): void
    {
        $this->wiki->forceDelete();
        parent::tearDown();
    }

    public function testNoJobs()
    {
        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => Http::response([
                'query' => [
                    'statistics' => [
                        'jobs' => 0
                    ]
                ]
            ], 200)
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('enqueueWiki');
        $mockJob->expects($this->once())->method('hasPendingJobs')->with($this->wiki->getAttribute('domain'));

        $job = new PollForMediaWikiJobsJob();
        $job->setJob($mockJob);

        $job->handle();
    }
}
