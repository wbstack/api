<?php

namespace Tests\Commands;

use App\QsBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SpliBatchesTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        QsBatch::query()->delete();
    }

    public function tearDown(): void
    {
        QsBatch::query()->delete();
        parent::tearDown();
    }
    public function testEmpty()
    {
        $this->artisan('wbs-qs-batches:split', [])->assertExitCode(0);
    }

    public function testSplitting()
    {
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 88, 'entityIds' => 'P1,P2,P3,P4,P5,P6,P7,P8,P9,P10,P11,P12,P13,P14,P15,P16,P17,P18,P19,P20,P21,P22']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 55, 'eventTo' => 66, 'wiki_id' => 99, 'entityIds' => 'Q99,Q100']);
        QsBatch::factory()->create(['id' => 3, 'done' => 1, 'eventFrom' => 77, 'eventTo' => 99, 'wiki_id' => 111, 'entityIds' => 'P1,P2,P3,P4,P5,P6,P7,P8,P9,P10,P11,P12,P13,P14,P15,P16,P17,P18,P19,P20,P21,P22']);

        $this->artisan('wbs-qs-batches:split', [])->assertExitCode(0);

        $allBatches = QsBatch::query()->get();

        $this->assertEquals(5, $allBatches->count());
        $this->assertEquals('P1,P2,P3,P4,P5,P6,P7,P8,P9,P10', $allBatches->values()->get(0)->entityIds);
        $this->assertEquals('Q99,Q100', $allBatches->values()->get(1)->entityIds);
        $this->assertEquals('P1,P2,P3,P4,P5,P6,P7,P8,P9,P10,P11,P12,P13,P14,P15,P16,P17,P18,P19,P20,P21,P22', $allBatches->values()->get(2)->entityIds);
        $this->assertEquals('P11,P12,P13,P14,P15,P16,P17,P18,P19,P20', $allBatches->values()->get(3)->entityIds);
        $this->assertEquals(88, $allBatches->values()->get(3)->wiki_id);
        $this->assertEquals('P21,P22', $allBatches->values()->get(4)->entityIds);
        $this->assertEquals(88, $allBatches->values()->get(4)->wiki_id);
    }
}
