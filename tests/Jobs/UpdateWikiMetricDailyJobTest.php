<?php

namespace Jobs;

use App\Jobs\UpdateWikiMetricDailyJob;
use App\Wiki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateWikiMetricDailyJobTest extends TestCase
{
    use RefreshDatabase;

    public function dispatchJob()
    {
        Queue::fake();

        UpdateWikiMetricDailyJob::dispatch();

        Queue::assertPushed(UpdateWikiMetricDailyJob::class);
    }

    public function successfullyAddRecords()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        (new UpdateWikiMetricDailyJob($wiki))->handle();

        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'date' => now()->toDateString()
        ]);
    }

    /** @test
    public function doesNotAddDuplicateRecordsWithOnlyDateChange()
    {

    }*/
}

