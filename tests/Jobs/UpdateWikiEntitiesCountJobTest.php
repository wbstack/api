<?php

namespace Tests\Jobs;

use App\Jobs\UpdateWikiEntitiesCountJob;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class UpdateWikiEntitiesCountJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testEmpty()
    {
        Bus::fake();
        Http::fake();

        Bus::assertNothingDispatched();
        Http::assertNothingSent();
    }

    /**
     * @throws Exception
     */
    public function testSuccess()
    {
        Bus::fake();
        Wiki::factory()->create(['domain' => 'testwiki1.wikibase.cloud']);

        //Faking an API query that contain all pages in NAMESPACE 120 (properties) and 122 (items)
        //The API query that returns all pages with NAMESPACE 122 (items) was divided into 2 batches to test if the job...
        //...is able to get all the data in case the wiki has a big database and the query couldn't return everything in one go
        //more info: https://www.mediawiki.org/wiki/API:Continue
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

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
            ->method('fail')
            ->withAnyParameters();
        $job = new UpdateWikiEntitiesCountJob();
        $job->setJob($mockJob);
        $job->handle();

        $event = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki1.wikibase.cloud'])->first()->wikiEntitiesCount()->first();

        $this->assertEquals(9, $event['items_count']);
        $this->assertEquals(3, $event['properties_count']);
    }

    /**
     * @throws Exception
     */
    public function testEmptyWiki()
    {
        Bus::fake();
        Wiki::factory()->create(['domain' => 'testwiki2.wikibase.cloud']);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([
                'query' => [
                    'allpages' => []
                ],
            ], 200),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([
                'query' => [
                    'allpages' => []
                ],
            ], 200),
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
            ->method('fail')
            ->withAnyParameters();
        $job = new UpdateWikiEntitiesCountJob();
        $job->setJob($mockJob);
        $job->handle();

        $event = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki2.wikibase.cloud'])->first()->wikiEntitiesCount()->first();

        $this->assertEquals(0, $event['items_count']);
        $this->assertEquals(0, $event['properties_count']);
    }

    public function testMediawikiApiResponseError()
    {
        Bus::fake();
        Wiki::factory()->create(['domain' => 'testwiki3.wikibase.cloud']);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([],500),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([],500)
        ]);

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiEntitiesCountJob();
        $mockJob->expects($this->never())
            ->method('fail')
            ->withAnyParameters();
        $job->setJob($mockJob);
        $job->handle();

        $result = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki3.wikibase.cloud'])->first()->wikiEntitiesCount()->count();

        $this->assertEquals(0, $result);
        $this->assertEquals(0, $result);
    }
}
