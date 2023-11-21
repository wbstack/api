<?php

namespace Tests\Jobs;

use App\QsBatch;
use App\QsCheckpoint;
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
        QsCheckpoint::query()->delete();
        QsCheckpoint::init();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        QsBatch::query()->delete();
        EventPageUpdate::query()->delete();
        QsCheckpoint::query()->delete();
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
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 0, 'eventTo' => 2, 'wiki_id' => 88, 'entityIds' => 'Q23,P1']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 0, 'eventTo' => 0, 'wiki_id' => 99, 'entityIds' => 'Q99,Q100']);
        QsCheckpoint::set(2);
        EventPageUpdate::factory()->create(['id' => 1, 'wiki_id' => 111, 'namespace' => 120, 'title' => 'Q21']);
        EventPageUpdate::factory()->create(['id' => 3, 'wiki_id' => 111, 'namespace' => 120, 'title' => 'Q12']);

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
        $this->assertEquals($existingBatches->values()->get(2)->entityIds, 'Q11,Q12');
        $this->assertEquals($existingBatches->values()->get(3)->entityIds, 'P11,P12,P13,P14,P15,P16,P17,P18,P19,P20');

        // Test if we prevent items from being fed into the updater multiple times
        EventPageUpdate::factory()->create(['id' => 125, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q999']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $existingBatches = QsBatch::where(['wiki_id' => 99])->get();
        $this->assertEquals($existingBatches->count(), 4);
        $this->assertEquals($existingBatches->values()->get(2)->entityIds, 'Q999,Q11,Q12');
    }

    function testBackpressure(): void
    {
        Wiki::factory()->create(['id' => 99, 'domain' => 'test.wikibase.cloud']);
        EventPageUpdate::factory()->create(['id' => 124, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q1']);
        EventPageUpdate::factory()->create(['id' => 125, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q2']);
        EventPageUpdate::factory()->create(['id' => 126, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q3']);
        EventPageUpdate::factory()->create(['id' => 127, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q4']);
        EventPageUpdate::factory()->create(['id' => 128, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q5']);
        EventPageUpdate::factory()->create(['id' => 129, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q6']);
        EventPageUpdate::factory()->create(['id' => 130, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q7']);
        EventPageUpdate::factory()->create(['id' => 131, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q8']);
        EventPageUpdate::factory()->create(['id' => 132, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q9']);
        EventPageUpdate::factory()->create(['id' => 133, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q11']);
        EventPageUpdate::factory()->create(['id' => 134, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q12']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $this->assertEquals(2, QsBatch::query()->count());
    }

    function testCheckpoints(): void
    {
        Wiki::factory()->create(['id' => 99, 'domain' => 'test.wikibase.cloud']);
        EventPageUpdate::factory()->create(['id' => 124, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q1']);
        EventPageUpdate::factory()->create(['id' => 125, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q2']);
        EventPageUpdate::factory()->create(['id' => 126, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q3']);
        EventPageUpdate::factory()->create(['id' => 127, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q4']);
        EventPageUpdate::factory()->create(['id' => 128, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q5']);
        EventPageUpdate::factory()->create(['id' => 131, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q8']);
        EventPageUpdate::factory()->create(['id' => 132, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q9']);
        EventPageUpdate::factory()->create(['id' => 133, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q11']);
        EventPageUpdate::factory()->create(['id' => 134, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q12']);
        EventPageUpdate::factory()->create(['id' => 188, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q12']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $this->assertEquals(188, QsCheckpoint::where(['id' => QsCheckpoint::CHECKPOINT_ID])->first()->checkpoint);

        EventPageUpdate::factory()->create(['id' => 199, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q127']);
        EventPageUpdate::factory()->create(['id' => 198, 'wiki_id' => 99, 'namespace' => 120, 'title' => 'Q126']);

        $mockJob = $this->createMock(Job::class);
        $job = new CreateQueryserviceBatchesJob();
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();

        $this->assertEquals(199, QsCheckpoint::where(['id' => QsCheckpoint::CHECKPOINT_ID])->first()->checkpoint);
    }
}
