<?php

namespace Tests\Jobs;

use App\Wiki;
use App\Jobs\PollForMediaWikiJobsJob;
use App\Jobs\ProcessMediaWikiJobsJob;
use Tests\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;

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

        Bus::fake();
        $mockJob = $this->createMock(Job::class);
        $job = new PollForMediaWikiJobsJob();
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $job->handle();
        Bus::assertNothingDispatched();
    }

    public function testWithJobs()
    {
        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => Http::response([
                'query' => [
                    'statistics' => [
                        'jobs' => 3
                    ]
                ]
            ], 200)
        ]);
        Bus::fake();

        $mockJob = $this->createMock(Job::class);

        $job = new PollForMediaWikiJobsJob();
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $job->handle();
        Bus::assertDispatched(ProcessMediaWikiJobsJob::class);
    }
}
