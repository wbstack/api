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

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        QsBatch::query()->delete();
        EventPageUpdate::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        QsBatch::query()->delete();
        EventPageUpdate::query()->delete();
        parent::tearDown();
    }

    public function testEmpty (): void
    {
        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();
    }

    public function testBatchCreation (): void
    {
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

    public function testBatchMerging(): void
    {
        Wiki::factory()->create(['id' => 88, 'domain' => 'test1.wikibase.cloud']);
        Wiki::factory()->create(['id' => 99, 'domain' => 'test2.wikibase.cloud']);
        Wiki::factory()->create(['id' => 111, 'domain' => 'test3.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 88, 'entityIds' => 'Q23,P1']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 0, 'eventTo' => 0, 'wiki_id' => 99, 'entityIds' => 'Q99,Q100']);
        EventPageUpdate::factory()->create(['id' => 123, 'wiki_id' => 111, 'namespace' => 120, 'title' => 'Q12']);
        EventPageUpdate::factory()->create(['id' => 234, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q34']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $existingBatch = QsBatch::where(['wiki_id' => 99])->first();

        $this->assertNotNull($existingBatch);
        $this->assertEquals($existingBatch->entityIds, 'Q34,Q99,Q100');
        $this->assertEquals(3, QsBatch::query()->count());
    }

    function testBigBatches(): void
    {
        Wiki::factory()->create(['id' => 88, 'domain' => 'test1.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 88, 'entityIds' => 'Q1,Q2,Q3,Q4,Q5,Q6,Q7,Q8,Q9,Q10']);
        EventPageUpdate::factory()->create(['id' => 123, 'wiki_id' => 88, 'namespace' => 120, 'title' => 'Q11']);

        Wiki::factory()->create(['id' => 99, 'domain' => 'test2.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'Q1,Q2,Q3,Q4,Q5,Q6,Q7,Q8,Q9,Q10']);
        QsBatch::factory()->create(['id' => 3, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'P1,P2,P3,P4,P5,P6,P7,P8,P9,P10']);
        QsBatch::factory()->create(['id' => 4, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'Q12']);
        QsBatch::factory()->create(['id' => 5, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'P11,P12,P13,P14,P15,P16,P17,P18,P19,P20']);
        EventPageUpdate::factory()->create(['id' => 124, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q11']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        // This wiki should have created an entirely new batch
        $existingBatches = QsBatch::where(['wiki_id' => 88])->get();
        $this->assertEquals($existingBatches->count(), 2);
        $this->assertEquals($existingBatches->values()->get(0)->entityIds, 'Q1,Q2,Q3,Q4,Q5,Q6,Q7,Q8,Q9,Q10');
        $this->assertEquals($existingBatches->values()->get(1)->entityIds, 'Q11');

        // This wiki should have skipped the batches that have hit the limit and append to the next
        // best match
        $existingBatches = QsBatch::where(['wiki_id' => 99])->get();
        $this->assertEquals($existingBatches->count(), 4);
        $this->assertEquals($existingBatches->values()->get(0)->entityIds, 'Q1,Q2,Q3,Q4,Q5,Q6,Q7,Q8,Q9,Q10');
        $this->assertEquals($existingBatches->values()->get(1)->entityIds, 'P1,P2,P3,P4,P5,P6,P7,P8,P9,P10');
        $this->assertEquals($existingBatches->values()->get(2)->entityIds, 'P11,P12,P13,P14,P15,P16,P17,P18,P19,P20');
        // The batch that has been updated is pushed to the bottom as it's being recreated with a new id
        $this->assertEquals($existingBatches->values()->get(3)->entityIds, 'Q11,Q12');
    }
}
