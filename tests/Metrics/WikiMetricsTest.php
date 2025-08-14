<?php

namespace Tests\Metrics;

use App\Metrics\App\WikiMetrics;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDb;
use App\WikiDailyMetrics;
use App\Jobs\ProvisionWikiDbJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    public function testItSaveTripleCountSuccessfully()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewikiforunittest.wikibase.cloud'
        ]);
        $wikiDb = WikiDb::first();
        $wikiDb->update( ['wiki_id' => $wiki->id] );
        $namespace = 'asdf';
        $host = config('app.queryservice_host');

        $dbRow = QueryserviceNamespace::create([
            'namespace' => $namespace,
            'backend' => $host,
        ]);

        DB::table('queryservice_namespaces')->where(['id'=>$dbRow->id])->limit(1)->update(['wiki_id' => $wiki->id]);
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        Http::fake([
            '*' => Http::response([
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
        $wikiDb = WikiDb::first();
        $wikiDb->update( ['wiki_id' => $wiki->id] );
        $namespace = 'asdf';
        $host = config('app.queryservice_host');

        $dbRow = QueryserviceNamespace::create([
            'namespace' => $namespace,
            'backend' => $host,
        ]);

        DB::table('queryservice_namespaces')->where(['id'=>$dbRow->id])->limit(1)->update(['wiki_id' => $wiki->id]);
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        Http::fake([
            '*' => Http::response('Error', 500)
        ]);
        (new WikiMetrics())->saveMetrics($wiki);
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'number_of_triples' => null
        ]);
    }

    public function testSavesEntityCountsCorrectly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'entitycounttest.wikibase.cloud'
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        $tablePage = $wikiDb->name . '.' . $wikiDb->prefix . '_page';

        DB::statement("CREATE TABLE IF NOT EXISTS $tablePage (
        page_id INT AUTO_INCREMENT PRIMARY KEY,
        page_namespace INT,
        page_is_redirect TINYINT(1) DEFAULT 0,
        page_title VARCHAR(255),
        page_random DOUBLE,
        page_touched BINARY(14),
        page_latest INT,
        page_len INT

    )");

        // Insert dummy data
        DB::table($tablePage)->insert([
            [
                'page_namespace' => 120,
                'page_is_redirect' => 0,
                'page_title' => 'foo',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 1,
                'page_len' => 2
            ], // item
            [
                'page_namespace' => 120,
                'page_is_redirect' => 0,
                'page_title' => 'bar',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 0,
                'page_len' => 2
            ], // item_rand
            [
                'page_namespace' => 122,
                'page_is_redirect' => 0,
                'page_title' => 'foo',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 1,
                'page_len' => 2]
            , // property
            [
                'page_namespace' => 640,
                'page_is_redirect' => 0,
                'page_title' => 'bar',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 1,
                'page_len' => 2
            ], // entity schema
            [
                'page_namespace' => 146,
                'page_is_redirect' => 0,
                'page_title' => 'foo',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 1,
                'page_len' => 2
            ], // lexeme
            [
                'page_namespace' => 640,
                'page_is_redirect' => 1,
                'page_title' => 'foo',
                'page_random' => 0,
                'page_touched' => random_bytes(10),
                'page_latest' => 1,
                'page_len' => 2
            ], // entity schema (redirect, ignored)
        ]);
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . now()->subDay()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => now()->subDay()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);

        (new WikiMetrics())->saveMetrics($wiki);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'item_count' => 2,
            'property_count' => 1,
            'lexeme_count' => 1,
            'entity_schema_count' => 1 // the redirect should be ignored
        ]);

    }
}

