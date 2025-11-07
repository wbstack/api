<?php

namespace Tests\Jobs;

use App\Jobs\PollForMediaWikiJobsJob;
use App\Jobs\ProcessMediaWikiJobsJob;
use App\Wiki;
use App\Services\MediaWikiHostResolver;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PollForMediaWikiJobsJobTest extends TestCase {
    use RefreshDatabase;

    private Model $wiki;

    private $mwBackendHost;

    private $mockMwHostResolver;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();

        $this->mwBackendHost = 'mediawiki.localhost';

        $this->mockMwHostResolver = $this->createMock(MediaWikiHostResolver::class);
        $this->mockMwHostResolver->method('getBackendHostForDomain')->willReturn(
            $this->mwBackendHost
        );
    }

    public function testNoJobs() {
        Http::fake([
            $this->mwBackendHost . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => Http::response([
                'query' => [
                    'statistics' => [
                        'jobs' => 0,
                    ],
                ],
            ], 200),
        ]);

        Bus::fake();
        $mockJob = $this->createMock(Job::class);
        $job = new PollForMediaWikiJobsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);
        Bus::assertNothingDispatched();
    }

    public function testWithJobs() {
        Http::fake([
            $this->mwBackendHost . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => Http::response([
                'query' => [
                    'statistics' => [
                        'jobs' => 3,
                    ],
                ],
            ], 200),
        ]);
        Bus::fake();

        $mockJob = $this->createMock(Job::class);

        $job = new PollForMediaWikiJobsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);
        Bus::assertDispatched(ProcessMediaWikiJobsJob::class);
    }

    public function testWithFailure() {
        Http::fake([
            $this->mwBackendHost . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => Http::response([
                'error' => 'Something went wrong',
            ], 500),
        ]);
        Bus::fake();

        $mockJob = $this->createMock(Job::class);

        $job = new PollForMediaWikiJobsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->once())->method('markAsFailed');
        $mockJob->expects($this->never())->method('fail');
        $job->handle($this->mockMwHostResolver);
        Bus::assertNothingDispatched();
    }
}
