<?php

namespace Tests\Jobs;

use App\Wiki;
use App\Jobs\UpdateWikiSiteStatsJob;
use Tests\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Illuminate\Database\Eloquent\Model;

class UpdateWikiSiteStatsJobTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void {
        // Other tests leave dangling wikis around so we need to clean them up
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
            'domain' => 'this.wikibase.cloud'
        ]);
        Wiki::factory()->create([
            'domain' => 'that.wikibase.cloud'
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() !== getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json') {
                return Http::response('not found', 404);
            }

            switch ($request->header('host')[0]) {
            case 'this.wikibase.cloud':
                return Http::response(
                    ['query' => [
                        'statistics' => [
                            'pages' => 1,
                            'articles' => 2,
                            'edits' => 3,
                            'images' => 4,
                            'users' => 5,
                            'activeusers' => 6,
                            'admins' => 7,
                            'jobs' => 8,
                            'cirrussearch-article-words' => 9
                        ]
                    ]],
                    200,
                );
            case 'that.wikibase.cloud':
                return Http::response(
                    ['query' => [
                        'statistics' => [
                            'pages' => 19,
                            'articles' => 18,
                            'edits' => 17,
                            'images' => 16,
                            'users' => 15,
                            'activeusers' => 14,
                            'admins' => 13,
                            'jobs' => 12,
                            'cirrussearch-article-words' => 11
                        ]
                    ]],
                    200,
                );
                default:
                    return Http::response('not found', 404);
            }
        });

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob();
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle();

        $stats1 = Wiki::with('wikiSiteStats')->where(['domain' => 'this.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats1['admins'], 7);

        $stats2 = Wiki::with('wikiSiteStats')->where(['domain' => 'that.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats2['jobs'], 12);
    }

    public function testFailure()
    {
        Wiki::factory()->create([
            'domain' => 'fail.wikibase.cloud'
        ]);
        Wiki::factory()->create([
            'domain' => 'that.wikibase.cloud'
        ]);
        Wiki::factory()->create([
            'domain' => 'incomplete.wikibase.cloud'
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() !== getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json') {
                return Http::response('not found', 404);
            }

            switch ($request->header('host')[0]) {
            case 'fail.wikibase.cloud':
                return Http::response('DINOSAUR OUTBREAK!', 500);
            case 'that.wikibase.cloud':
                return Http::response(
                    ['query' => [
                        'statistics' => [
                            'pages' => 1,
                            'articles' => 2,
                            'edits' => 3,
                            'images' => 4,
                            'users' => 5,
                            'activeusers' => 6,
                            'admins' => 7,
                            'jobs' => 8,
                            'cirrussearch-article-words' => 9
                        ]
                    ]],
                    200,
                );
            case 'incomplete.wikibase.cloud':
                return Http::response(
                    ['query' => [
                        'statistics' => [
                            'articles' => 99,
                            'not' => 129,
                            'sure' => 11,
                            'what' => 102,
                            'happened' => 20
                        ]
                    ]],
                    200,
                );
                default:
                    return Http::response('not found', 404);
            }
        });

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob();
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->once())->method('markAsFailed');
        $job->handle();

        $stats1 = Wiki::with('wikiSiteStats')->where(['domain' => 'that.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats1['admins'], 7);

        $stats2 = Wiki::with('wikiSiteStats')->where(['domain' => 'incomplete.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats2['articles'], 99);
        $this->assertEquals($stats2['images'], 0);
    }
}
