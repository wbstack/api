<?php

namespace Tests\Jobs;

use App\QsBatch;
use App\Jobs\RequeuePendingBatchesJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\Job;
use Carbon\Carbon;

class RequeuePendingBatchesJobTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void {
        // Other tests leave dangling wikis around so we need to clean them up
        parent::setUp();
        QsBatch::query()->delete();
    }

    public function tearDown(): void {
        QsBatch::query()->delete();
        parent::tearDown();
    }

    public function testRequeue (): void {
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(200), 'id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(400), 'id' => 2, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['processing_attempts' => 3, 'id' => 3, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 1, 'entityIds' => 'a,b']);

        $mockJob = $this->createMock(Job::class);
        $job = new RequeuePendingBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $this->assertEquals(QsBatch::where('pending_since', '=', null)->count(), 2);
        $this->assertEquals(QsBatch::where('failed', '=', true)->count(), 1);
    }
}
