<?php

namespace Tests\Jobs;

use App\Jobs\RequeuePendingQsBatchesJob;
use App\QsBatch;
use Carbon\Carbon;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequeuePendingQsBatchesJobTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        // Other tests leave dangling wikis around so we need to clean them up
        parent::setUp();
        QsBatch::query()->delete();
    }

    protected function tearDown(): void {
        QsBatch::query()->delete();
        parent::tearDown();
    }

    public function testRequeue(): void {
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(200), 'id' => 1, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(400), 'id' => 2, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['processing_attempts' => 3, 'id' => 3, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['failed' => 1, 'processing_attempts' => 4, 'id' => 4, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['failed' => 0, 'processing_attempts' => 3, 'id' => 5, 'done' => 1, 'wiki_id' => 1, 'entityIds' => 'a,b']);

        $mockExceptionHandler = $this->createMock(ExceptionHandler::class);
        $mockExceptionHandler
            ->expects($this->once())
            ->method('report');
        $this->app->instance(ExceptionHandler::class, $mockExceptionHandler);

        $mockJob = $this->createMock(Job::class);
        $job = new RequeuePendingQsBatchesJob;
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $this->assertEquals(QsBatch::where('pending_since', '=', null)->count(), 4);
        $this->assertEquals(QsBatch::where('failed', '=', true)->count(), 2);
        $this->assertEquals(QsBatch::where('id', '=', 2)->first()->processing_attempts, 1);
        $this->assertEquals(QsBatch::where('id', '=', 4)->first()->processing_attempts, 4);
        $this->assertEquals(QsBatch::where('id', '=', 4)->first()->pending_since, null);
        $this->assertEquals(QsBatch::where('id', '=', 5)->first()->failed, 0);
    }
}
