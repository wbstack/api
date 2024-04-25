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

    /**
     * @throws Exception
     */
    public function testFailure()
    {
        Bus::fake();
        Wiki::factory()->create(['domain' => 'testwiki3.wikibase.cloud']);

        Http::fake([
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=120&apcontinue=&aplimit=max&format=json' => Http::response([]),
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=allpages&apnamespace=122&apcontinue=&aplimit=max&format=json' => Http::response([])
        ]);

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiEntitiesCountJob();
        $job->setJob($mockJob);
        $job->handle();

        $event = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki3.wikibase.cloud'])->first()->wikiEntitiesCount()->first();

        $this->assertEquals(null, $event['items_count']);
        $this->assertEquals(null, $event['properties_count']);
    }
}
