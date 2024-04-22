<?php

namespace Tests\Jobs;

use App\Jobs\SiteStatsUpdateJob;
use App\Jobs\UpdateWikiEntitiesCountJob;
use App\Jobs\UpdateWikiSiteStatsJob;
use App\Wiki;
use App\WikiEntitiesCount;
use App\WikiSetting;
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

    // the job does not fail in general
//    public function testWikiEntitiesCountJob_Success()
//    {
//        $mockJob = $this->createMock(Job::class);
//        $mockJob->expects($this->never())
//            ->method('fail')
//            ->withAnyParameters();
//        $job = new updateWikiSiteStatsJob();
//        $job->setJob($mockJob);
//        $job->handle();
//    }

    /**
     * @throws Exception
     */
    public function testSuccess()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'testwiki.wikibase.cloud']);

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

        $event = Wiki::with('wikiEntitiesCount')->where(['domain' => 'testwiki.wikibase.cloud'])->first()->wikiEntitiesCount()->first();

        $this->assertEquals(9, $event['items_count']);
        $this->assertEquals(3, $event['properties_count']);
    }
}
