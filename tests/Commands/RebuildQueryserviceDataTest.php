<?php

namespace Tests\Commands;

use App\Wiki;
use App\QueryserviceNamespace;
use App\WikiSetting;
use App\Jobs\TemporaryDummyJob;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RebuildQueryserviceDataTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        QueryserviceNamespace::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        QueryserviceNamespace::query()->delete();
        parent::tearDown();
    }

    public function testEmpty()
    {
        Bus::fake();
        Http::fake();

        $this->artisan('wbs-qs:rebuild')->assertExitCode(0);

        Bus::assertNothingDispatched();
        Http::assertNothingSent();
    }

    public function testWikiWithLexemes()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);
        WikiSetting::factory()->create([
            'wiki_id' => $wiki->id,
            'name' => 'wwExtEnableWikibaseLexeme',
            'value' => '1',
        ]);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_12345',
            'backend' => 'test_backend',
        ]);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Property:P1',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P9',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P11',
                            'namespace' => 120,
                        ],
                    ],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max' => Http::response([
                'continue' => [
                    'apcontinue' => 'Q6',
                ],
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Item:Q1',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q2',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q3',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q4',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q5',
                            'namespace' => 122,
                        ],
                    ],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=Q6&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Item:Q6',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q7',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q8',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q9',
                            'namespace' => 122,
                        ],
                    ],
                ]
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Lexeme:L1',
                            'namespace' => 146,
                        ],
                        [
                            'title' => 'Lexeme:L2',
                            'namespace' => 146,
                        ],
                        [
                            'title' => 'Lexeme:L100',
                            'namespace' => 146,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(0);
        Bus::assertDispatchedTimes(TemporaryDummyJob::class, 2);
        Bus::assertDispatched(TemporaryDummyJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->domain) {
                return false;
            }
            if ('P1,P9,P11,Q1,Q2,Q3,Q4,Q5,Q6,Q7' !== $job->entites) {
                return false;
            }
            if ('http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/test_ns_12345/sparql' !== $job->sparqlUrl) {
                return false;
            }
            return true;
        });
        Bus::assertDispatched(TemporaryDummyJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->domain) {
                return false;
            }
            if ('Q8,Q9,L1,L2,L100' !== $job->entites) {
                return false;
            }
            if ('http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/test_ns_12345/sparql' !== $job->sparqlUrl) {
                return false;
            }
            return true;
        });
        Http::assertSentCount(4);
    }

    public function testWikiNoLexemes()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_12345',
            'backend' => 'test_backend',
        ]);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Property:P1',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P9',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P11',
                            'namespace' => 120,
                        ],
                    ],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Item:Q1',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q2',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q3',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q4',
                            'namespace' => 122,
                        ],
                        [
                            'title' => 'Item:Q5',
                            'namespace' => 122,
                        ],
                    ],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max' => Http::response([
                'error' => 'Lexemes not enabled for this wiki',
            ], 400),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(0);
        Bus::assertDispatched(TemporaryDummyJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->domain) {
                return false;
            }
            if ('P1,P9,P11,Q1,Q2,Q3,Q4,Q5' !== $job->entites) {
                return false;
            }
            if ('http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/test_ns_12345/sparql' !== $job->sparqlUrl) {
                return false;
            }
            return true;
        });
        Http::assertSentCount(2);
    }

    public function testFailure()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_12345',
            'backend' => 'test_backend',
        ]);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max' => Http::response([
                'query' => [
                    'allpages' => [
                        [
                            'title' => 'Property:P1',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P9',
                            'namespace' => 120,
                        ],
                        [
                            'title' => 'Property:P11',
                            'namespace' => 120,
                        ],
                    ],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max' => Http::response([
                'error' => 'THE DINOSAURS ESCAPED!',
            ], 500),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max' => Http::response([
                'error' => 'Lexemes not enabled for this wiki',
            ], 400),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(1);
        Bus::assertNothingDispatched();
    }
}
