<?php

namespace Tests\Commands;

use App\Wiki;
use App\QueryserviceNamespace;
use App\WikiSetting;
use App\Jobs\SpawnQueryserviceUpdaterJob;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=Q6&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max&format=json' => Http::response([
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
        Bus::assertDispatchedTimes(SpawnQueryserviceUpdaterJob::class, 2);
        Bus::assertDispatched(SpawnQueryserviceUpdaterJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->wikiDomain) {
                return false;
            }
            if (10 !== count(explode(',', $job->entities))) {
                return false;
            }
            if ('http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/test_ns_12345/sparql' !== $job->sparqlUrl) {
                return false;
            }
            return true;
        });
        Bus::assertDispatched(SpawnQueryserviceUpdaterJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->wikiDomain) {
                return false;
            }
            if (5 !== count(explode(',', $job->entities))) {
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max&format=json' => Http::response([
                'error' => 'Lexemes not enabled for this wiki',
            ], 400),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(0);
        Bus::assertDispatched(SpawnQueryserviceUpdaterJob::class, function ($job) {
            if ('rebuild.wikibase.cloud' !== $job->wikiDomain) {
                return false;
            }
            if (8 !== count(explode(',', $job->entities))) {
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
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
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
                'error' => 'THE DINOSAURS ESCAPED!',
            ], 500),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max&format=json' => Http::response([
                'error' => 'Lexemes not enabled for this wiki',
            ], 400),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(1);
        Bus::assertNothingDispatched();
    }

    public function testEmptyWiki()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_12345',
            'backend' => 'test_backend',
        ]);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
                'query' => [
                    'allpages' => [],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
                'query' => [
                    'allpages' => [],
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=146&apcontinue=&aplimit=max&format=json' => Http::response([
                'error' => 'Lexemes not enabled for this wiki',
            ], 400),
        ]);

        $this->artisan('wbs-qs:rebuild', ['--chunkSize' => 10])->assertExitCode(0);
        Bus::assertNothingDispatched();
        Http::assertSentCount(2);
    }

    public function testDomainArg()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_12345',
            'backend' => 'test_backend',
        ]);

        $wiki = Wiki::factory()->create(['domain' => 'whoops.wikibase.cloud']);
        QueryserviceNamespace::factory()->create([
            'wiki_id' => $wiki->id,
            'namespace' => 'test_ns_23456',
            'backend' => 'test_backend',
        ]);

        Http::fake(function (Request $request) {
            $hostHeader = $request->header('host')[0];
            if ($hostHeader !== 'rebuild.wikibase.cloud') {
                return Http::response('The dinosaurs escaped!!!', 500);
            }
            return Http::response([
                'query' => [
                    'allpages' => [],
                ],
            ], 200);
        });

        $this->artisan(
            'wbs-qs:rebuild',
            ['--chunkSize' => 10, '--domain' => ['whoops.wikibase.cloud']]
        )->assertExitCode(1);
        Bus::assertNothingDispatched();
        Http::assertSentCount(1);
    }
}
