<?php

namespace Tests\Jobs;

use App\QsBatch;
use App\Wiki;
use App\EventPageUpdate;
use App\Jobs\CreateQueryserviceBatchesJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\Job;

class CreateQueryserviceBatchesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
        QsBatch::query()->delete();
        EventPageUpdate::query()->delete();
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        QsBatch::query()->delete();
        EventPageUpdate::query()->delete();
        parent::tearDown();
    }

    public function testEmpty (): void {
        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();
    }

    public function testBatchCreation (): void {
        Wiki::factory()->create(['id' => 88, 'domain' => 'test1.wikibase.cloud']);
        Wiki::factory()->create(['id' => 99, 'domain' => 'test2.wikibase.cloud']);
        Wiki::factory()->create(['id' => 111, 'domain' => 'test3.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 88, 'entityIds' => 'Q23,P1']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 0, 'eventTo' => 0, 'wiki_id' => 99, 'entityIds' => 'Q99,Q100']);
        EventPageUpdate::factory()->create(['wiki_id' => 111, 'namespace' => 120, 'title' => 'Q12']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $newBatch = QsBatch::where(['wiki_id' => 111])->first();

        $this->assertNotNull($newBatch);
        $this->assertEquals($newBatch->entityIds, 'Q12');
        $this->assertEquals(QsBatch::query()->count(), 3);
    }
}
