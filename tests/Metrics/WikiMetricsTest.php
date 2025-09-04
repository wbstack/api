<?php

namespace Tests\Metrics;

use App\Jobs\ProvisionWikiDbJob;
use App\Metrics\App\WikiMetrics;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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

    public static function dummyDataProvider() {
        $item1 = [
            'page_namespace' => 120,
            'page_is_redirect' => 0,
            'page_title' => 'foo',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 1,
            'page_len' => 2,
        ];

        $item2 = [
            'page_namespace' => 120,
            'page_is_redirect' => 0,
            'page_title' => 'bar',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 0,
            'page_len' => 2,
        ];
            
        $property = [
            'page_namespace' => 122,
            'page_is_redirect' => 0,
            'page_title' => 'foo',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 1,
            'page_len' => 2,
        ];

        $entitySchema = [
            'page_namespace' => 640,
            'page_is_redirect' => 0,
            'page_title' => 'bar',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 1,
            'page_len' => 2,
        ];

        $entitySchemaRedirect = [
            'page_namespace' => 640,
            'page_is_redirect' => 1,
            'page_title' => 'foo',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 1,
            'page_len' => 2,
        ];

        $lexeme = [
            'page_namespace' => 146,
            'page_is_redirect' => 0,
            'page_title' => 'foo',
            'page_random' => 0,
            'page_touched' => random_bytes(10),
            'page_latest' => 1,
            'page_len' => 2,
        ];

        // all relevant data types
        yield [
            'expectedItemCount' => 2,
            'expectedPropertyCount' => 1,
            'expectedLexemeCount' => 1,
            'expectedEntitySchemaCount' => 1,

            'pageData' => [
                $item1,
                $item2,
                $property,
                $lexeme,
                $entitySchema,
                $entitySchemaRedirect,
            ],
        ];
        
        // zero items
        yield [
            'expectedItemCount' => 0,
            'expectedPropertyCount' => 1,
            'expectedLexemeCount' => 1,
            'expectedEntitySchemaCount' => 1,
            
            'pageData' => [
                // $item1,
                // $item2,
                $property,
                $lexeme,
                $entitySchema,
                $entitySchemaRedirect,
            ],
        ];

        // zero properties
        yield [
            'expectedItemCount' => 2,
            'expectedPropertyCount' => 0,
            'expectedLexemeCount' => 1,
            'expectedEntitySchemaCount' => 1,
            
            'pageData' => [
                $item1,
                $item2,
                // $property,
                $lexeme,
                $entitySchema,
                $entitySchemaRedirect,
            ],
        ];

        // zero Lexemes
        yield [
            'expectedItemCount' => 2,
            'expectedPropertyCount' => 1,
            'expectedLexemeCount' => 0,
            'expectedEntitySchemaCount' => 1,
            
            'pageData' => [
                $item1,
                $item2,
                $property,
                // $lexeme,
                $entitySchema,
                $entitySchemaRedirect,
            ],
        ];

        // zero EntitySchemas
        yield [
            'expectedItemCount' => 2,
            'expectedPropertyCount' => 1,
            'expectedLexemeCount' => 1,
            'expectedEntitySchemaCount' => 0,
            
            'pageData' => [
                $item1,
                $item2,
                $property,
                $lexeme,
                // $entitySchema,
                $entitySchemaRedirect, // should not count
            ],
        ];
    }

    /**
     * @dataProvider dummyDataProvider
     */
    public function testSavesEntityCountsCorrectly($expectedItemCount, $expectedPropertyCount, $expectedLexemeCount, $expectedEntitySchemaCount, $pageData) {
        $wiki = Wiki::factory()->create([
            'domain' => 'entitycounttest.wikibase.cloud',
        ]);

        $wikiDb = WikiDb::first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        $tablePage = $wikiDb->name . '.' . $wikiDb->prefix . '_page';

        Schema::dropIfExists($tablePage);
        Schema::create($tablePage, function (Blueprint $table) {
            $table->increments('page_id');
            $table->integer('page_namespace');
            $table->boolean('page_is_redirect')->default(0);
            $table->string('page_title', 255);
            $table->double('page_random');
            $table->binary('page_touched');
            $table->integer('page_latest');
            $table->integer('page_len');
        });

        // Insert dummy data
        DB::table($tablePage)->insert($pageData);
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . now()->subDay()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => now()->subDay()->toDateString(),
            'pages' => count($pageData),
            'is_deleted' => 0,
        ]);

        (new WikiMetrics)->saveMetrics($wiki);

        // clean up after the test
        $wiki->forceDelete();
        Schema::dropIfExists($tablePage);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'item_count' => $expectedItemCount,
            'property_count' => $expectedPropertyCount,
            'lexeme_count' => $expectedLexemeCount,
            'entity_schema_count' => $expectedEntitySchemaCount, // redirects should be ignored
        ]);
    }
}
