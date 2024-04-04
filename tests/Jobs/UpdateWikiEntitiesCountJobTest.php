<?php

namespace Tests\Jobs;

use App\Wiki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateWikiEntitiesCountJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        parent::tearDown();
    }

    public function testSuccess()
    {
        Wiki::factory()->create([
            'domain' => 'testwiki.wikibase.cloud'
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
            ], 200)
        ]);

        $event = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki.wikibase.cloud'])->first()->wikiEntitiesCount()->first();
        $this->assertEquals(9, $event['items_count']);
        $this->assertEquals(3, $event['properties_count']);
    }
}
