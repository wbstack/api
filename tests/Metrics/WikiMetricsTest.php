<?php

namespace Tests\Metrics;

use App\Jobs\ProvisionWikiDbJob;
use App\Metrics\App\WikiMetrics;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikiMetricsTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $manager = $this->app->make('db');
        $job = new ProvisionWikiDbJob;
        $job->handle($manager);
    }

    public function testSuccessfullyAddRecords() {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud',
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        (new WikiMetrics)->saveMetrics($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'date' => now()->toDateString(),
        ]);
    }

    public function testDoesNotAddDuplicateRecordsWithOnlyDateChange() {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud',
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        // Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0,
        ]);
        (new WikiMetrics)->saveMetrics($wiki);

        // Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function testAddRecordsWikiIsDeleted() {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud',
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        // Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 1,
        ]);
        // delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics)->saveMetrics($wiki);

        // Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString(),
        ]);
    }

    public function testItSaveTripleCountSuccessfully() {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewikiforunittest.wikibase.cloud',
        ]);
        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);
        $namespace = 'asdf';
        $host = config('app.queryservice_host');

        $dbRow = QueryserviceNamespace::create([
            'namespace' => $namespace,
            'backend' => $host,
        ]);

        DB::table('queryservice_namespaces')->where(['id' => $dbRow->id])->limit(1)->update(['wiki_id' => $wiki->id]);
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0,
        ]);
        Http::fake([
            '*' => Http::response([
                'results' => [
                    'bindings' => [
                        [
                            'triples' => ['type' => 'literal', 'value' => '12345'],
                        ],
                    ],
                ],
            ], 200),
        ]);
        (new WikiMetrics)->saveMetrics($wiki);
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'number_of_triples' => 12345,
        ]);
    }

    public function testSaveNullForFailedRequestOfTriplesCount() {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewikitest.wikibase.cloud',
        ]);
        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);
        $namespace = 'asdf';
        $host = config('app.queryservice_host');

        $dbRow = QueryserviceNamespace::create([
            'namespace' => $namespace,
            'backend' => $host,
        ]);

        DB::table('queryservice_namespaces')->where(['id' => $dbRow->id])->limit(1)->update(['wiki_id' => $wiki->id]);
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0,
        ]);
        Http::fake([
            '*' => Http::response('Error', 500),
        ]);
        (new WikiMetrics)->saveMetrics($wiki);
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'number_of_triples' => null,
        ]);
    }
}
