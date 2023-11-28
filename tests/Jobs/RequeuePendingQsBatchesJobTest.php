<?php

namespace Tests\Jobs;

use App\QsBatch;
use App\Jobs\RequeuePendingQsBatchesJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\Job;
use Carbon\Carbon;
use Illuminate\Contracts\Debug\ExceptionHandler;

class RequeuePendingQsBatchesJobTest extends TestCase
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
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(200), 'id' => 1, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(400), 'id' => 2, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['processing_attempts' => 3, 'id' => 3, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'a,b']);

        $mockExceptionHandler = $this->createMock(ExceptionHandler::class);
        $mockExceptionHandler
            ->expects($this->once())
            ->method('report');
        $this->app->instance(ExceptionHandler::class, $mockExceptionHandler);

        $mockJob = $this->createMock(Job::class);
        $job = new RequeuePendingQsBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
          ->method('fail');
        $job->handle();

        $this->assertEquals(QsBatch::where('pending_since', '=', null)->count(), 2);
        $this->assertEquals(QsBatch::where('failed', '=', true)->count(), 1);
        $this->assertEquals(QsBatch::where('id', '=', 2)->first()->processing_attempts, 1);
    }
}
