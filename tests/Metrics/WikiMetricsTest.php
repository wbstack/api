<?php

namespace Tests\Metrics;

use App\Metrics\App\WikiMetrics;
use App\Wiki;
use App\WikiDb;
use App\WikiDailyMetrics;
use App\Jobs\ProvisionWikiDbJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void      {
        parent::setUp();
        $manager = $this->app->make('db');
        $job = new ProvisionWikiDbJob();
        $job->handle($manager);
    }

    public function testSuccessfullyAddRecords()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update( ['wiki_id' => $wiki->id] );

        (new WikiMetrics())->saveMetrics($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'date' => now()->toDateString()
        ]);
    }


    public function testDoesNotAddDuplicateRecordsWithOnlyDateChange()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update( ['wiki_id' => $wiki->id] );

        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveMetrics($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeleted()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update( ['wiki_id' => $wiki->id] );

        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 1
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveMetrics($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }
}

