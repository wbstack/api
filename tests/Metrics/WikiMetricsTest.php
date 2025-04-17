<?php

namespace Tests\Metrics;

use App\Metrics\App\WikiMetrics;
use App\Wiki;
use App\WikiDailyMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikiMetricsTest extends TestCase
{
    use RefreshDatabase;


    public function testSuccessfullyAddRecords()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

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
    public function testItSaveTripleCountSuccessfully()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewikiforunittest.wikibase.cloud'
        ]);
        Http::fake([
            'https://somewikiforunittest.wikibase.cloud/query/sparql*' => Http::response([
                'results' => [
                    'bindings' => [
                        [
                            'triples' => ['type' => 'literal', 'value' => '12345']
                        ]
                    ]
                ]
            ], 200)
        ]);
        (new WikiMetrics())->saveMetrics($wiki);
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'number_of_triples' => 12345
        ]);
    }
    public function testSaveNullForFailedRequestOfTriplesCount()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewikitest.wikibase.cloud'
        ]);
        Http::fake([
            'https://somewikitest.wikibase.cloud/query/sparql*' => Http::response('Error', 500)
        ]);
        (new WikiMetrics())->saveMetrics($wiki);
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'number_of_triples' => null
        ]);
    }
}

