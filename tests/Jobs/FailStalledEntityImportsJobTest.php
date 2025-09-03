<?php

namespace Tests\Jobs;

use App\Jobs\FailStalledEntityImportsJob;
use App\Wiki;
use App\WikiEntityImport;
use App\WikiEntityImportStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailStalledEntityImportsJobTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        WikiEntityImport::query()->delete();
        Wiki::query()->delete();
    }

    protected function tearDown(): void {
        WikiEntityImport::query()->delete();
        Wiki::query()->delete();
        parent::tearDown();
    }

    public function testFailsEligible() {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        WikiEntityImport::factory()->create([
            'wiki_id' => $wiki->id,
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now()->subMinutes(60),
        ]);
        WikiEntityImport::factory()->create([
            'wiki_id' => $wiki->id,
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now()->subDays(4),
        ]);
        WikiEntityImport::factory()->create([
            'wiki_id' => $wiki->id,
            'status' => WikiEntityImportStatus::Success,
            'started_at' => Carbon::now()->subDays(4),
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new FailStalledEntityImportsJob;
        $job->setJob($mockJob);
        $job->handle();

        $this->assertEquals(
            1,
            WikiEntityImport::where(['status' => WikiEntityImportStatus::Failed])->count(),
        );
        $this->assertEquals(
            1,
            WikiEntityImport::where(['status' => WikiEntityImportStatus::Pending])->count(),
        );
        $this->assertEquals(
            1,
            WikiEntityImport::where(['status' => WikiEntityImportStatus::Success])->count(),
        );
    }
}
