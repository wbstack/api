<?php

namespace Tests\Jobs;

use App\Jobs\UpdateWikiSiteStatsJob;
use App\Services\MediaWikiHostResolver;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateWikiSiteStatsJobTest extends TestCase {
    use RefreshDatabase;

    private $fakeResponses;

    private $mwBackendHost;

    private $mockMwHostResolver;

    protected function setUp(): void {
        // Other tests leave dangling wikis around so we need to clean them up
        parent::setUp();
        Wiki::query()->delete();
        $this->fakeResponses = [];
        Http::preventStrayRequests();

        $this->mwBackendHost = 'mediawiki.localhost';

        $this->mockMwHostResolver = $this->createMock(MediaWikiHostResolver::class);
        $this->mockMwHostResolver->method('getBackendHostForDomain')->willReturn(
            $this->mwBackendHost
        );
    }

    protected function tearDown(): void {
        Wiki::query()->delete();
        parent::tearDown();
    }

    private function addFakeSiteStatsResponse($site, $response) {
        $siteStatsUrl = $this->mwBackendHost . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json';
        $this->fakeResponses[$siteStatsUrl][$site] = $response;
    }

    private function addFakeRevisionTimestamp($site, $revid, $timestamp) {
        $revTimestampUrl = $this->mwBackendHost . '/w/api.php?action=query&format=json&prop=revisions&rvprop=timestamp&formatversion=2&revids=' . $revid;
        $this->fakeResponses[$revTimestampUrl][$site] = Http::response([
            'query' => [
                'pages' => [
                    [
                        'revisions' => [
                            [
                                'timestamp' => $timestamp,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addFakeEmptyRevisionList($site) {
        $firstRevisionIdUrl = $this->mwBackendHost . '/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=newer';
        $this->fakeResponses[$firstRevisionIdUrl][$site] = Http::response([
            'query' => [
                'allrevisions' => [],
            ],
        ]);
        $lastRevisionIdUrl = $this->mwBackendHost . '/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=older';
        $this->fakeResponses[$lastRevisionIdUrl][$site] = Http::response([
            'query' => [
                'allrevisions' => [],
            ],
        ]);
    }

    private function addFakeFirstRevisionId($site, $id) {
        $firstRevisionIdUrl = $this->mwBackendHost . '/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=newer';
        $this->fakeResponses[$firstRevisionIdUrl][$site] = Http::response([
            'query' => [
                'allrevisions' => [
                    [
                        'revisions' => [
                            [
                                'revid' => $id,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addFakeLastRevisionId($site, $id) {
        $lastRevisionIdUrl = $this->mwBackendHost . '/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=older';
        $this->fakeResponses[$lastRevisionIdUrl][$site] = Http::response([
            'query' => [
                'allrevisions' => [
                    [
                        'revisions' => [
                            [
                                'revid' => $id,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function fakeResponse() {
        $responses = $this->fakeResponses;
        $fakeFunction = function (Request $request) use ($responses) {

            $url = $request->url();
            $hostHeader = $request->header('host')[0];
            // N.B.: using `data_get` is not feasible here as the array keys
            // contain dots
            if (array_key_exists($url, $responses)) {
                if (array_key_exists($hostHeader, $responses[$url])) {
                    return $responses[$url][$hostHeader];
                }
            }

            return Http::response('not found', 404);
        };

        Http::fake($fakeFunction);
    }

    public function testWikiSiteStatsIsSuccessfullyUpdated() {
        Wiki::factory()->create([
            'domain' => 'this.wikibase.cloud',
        ]);

        $this->addFakeSiteStatsResponse(
            'this.wikibase.cloud',
            Http::response(['query' => [
                'statistics' => [
                    'pages' => 1,
                    'articles' => 2,
                    'edits' => 3,
                    'images' => 4,
                    'users' => 5,
                    'activeusers' => 6,
                    'admins' => 7,
                    'jobs' => 8,
                    'cirrussearch-article-words' => 9,
                ],
            ]])
        );
        $this->fakeResponse();

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);

        $stats1 = Wiki::with('wikiSiteStats')->where(['domain' => 'this.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats1['admins'], 7);

    }

    public function testSuccessOfMultipleWikisTogether() {

        Wiki::factory()->create([
            'domain' => 'that.wikibase.cloud',
        ]);
        Wiki::factory()->create([
            'domain' => 'this.wikibase.cloud',
        ]);

        $this->addFakeSiteStatsResponse(
            'this.wikibase.cloud',
            Http::response(['query' => [
                'statistics' => [
                    'pages' => 1,
                    'articles' => 2,
                    'edits' => 3,
                    'images' => 4,
                    'users' => 5,
                    'activeusers' => 6,
                    'admins' => 7,
                    'jobs' => 8,
                    'cirrussearch-article-words' => 9,
                ],
            ]])
        );

        $this->addFakeSiteStatsResponse(
            'that.wikibase.cloud',
            Http::response(['query' => [
                'statistics' => [
                    'pages' => 19,
                    'articles' => 18,
                    'edits' => 17,
                    'images' => 16,
                    'users' => 15,
                    'activeusers' => 14,
                    'admins' => 13,
                    'jobs' => 12,
                    'cirrussearch-article-words' => 11,
                ],
            ]])
        );

        $this->addFakeFirstRevisionId('this.wikibase.cloud', 2);
        $this->addFakeFirstRevisionId('that.wikibase.cloud', 2);
        $this->addFakeLastRevisionId('this.wikibase.cloud', 1);
        $this->addFakeLastRevisionId('that.wikibase.cloud', 1);
        $this->addFakeRevisionTimestamp('that.wikibase.cloud', 2, '2023-05-07T21:31:47Z');
        $this->addFakeRevisionTimestamp('this.wikibase.cloud', 2, '2023-02-27T16:57:06Z');
        $this->addFakeRevisionTimestamp('this.wikibase.cloud', 1, '2023-09-16T17:22:33Z');
        $this->addFakeRevisionTimestamp('that.wikibase.cloud', 1, '2023-09-19T11:35:09Z');
        $this->fakeResponse();

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);

        $stats1 = Wiki::with('wikiSiteStats')->where(['domain' => 'this.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats1['admins'], 7);
        $events1 = Wiki::with('wikiLifecycleEvents')->where(['domain' => 'this.wikibase.cloud'])->first()->wikiLifecycleEvents()->first();
        $this->assertEquals($events1['first_edited']->toIso8601String(), '2023-02-27T16:57:06+00:00');
        $this->assertEquals($events1['last_edited']->toIso8601String(), '2023-09-16T17:22:33+00:00');

        $stats2 = Wiki::with('wikiSiteStats')->where(['domain' => 'that.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats2['jobs'], 12);
        $events2 = Wiki::with('wikiLifecycleEvents')->where(['domain' => 'that.wikibase.cloud'])->first()->wikiLifecycleEvents()->first();
        $this->assertEquals($events2['first_edited']->toIso8601String(), '2023-05-07T21:31:47+00:00');
        $this->assertEquals($events2['last_edited']->toIso8601String(), '2023-09-19T11:35:09+00:00');
    }

    public function testJobFailsIfSiteStatsLookupFails() {
        Wiki::factory()->create([
            'domain' => 'fail.wikibase.cloud',
        ]);

        $this->addFakeSiteStatsResponse(
            'fail.wikibase.cloud',
            Http::response('DINOSAUR OUTBREAK!', 500)
        );

        $this->fakeResponse();

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->once())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);
    }

    public function testIncompleteSiteStatsDoesNotCauseFailure() {
        Wiki::factory()->create([
            'domain' => 'incomplete.wikibase.cloud',
        ]);

        $this->addFakeSiteStatsResponse(
            'incomplete.wikibase.cloud',
            Http::response(['query' => [
                'statistics' => [
                    'articles' => 99,
                    'not' => 129,
                    'sure' => 11,
                    'what' => 102,
                    'happened' => 20,
                ],
            ]])
        );

        $this->fakeResponse();

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);

        $stats2 = Wiki::with('wikiSiteStats')->where(['domain' => 'incomplete.wikibase.cloud'])->first()->wikiSiteStats()->first();
        $this->assertEquals($stats2['articles'], 99);
        $this->assertEquals($stats2['images'], 0);
    }

    public function testNeverEditedWikiCreatesEmptyLifecycleEvents() {
        Wiki::factory()->create([
            'domain' => 'this.wikibase.cloud',
        ]);

        $this->addFakeSiteStatsResponse('this.wikibase.cloud', Http::response());
        $this->addFakeEmptyRevisionList('this.wikibase.cloud');
        $this->fakeResponse();

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob;
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle($this->mockMwHostResolver);

        $events = Wiki::with('wikiLifecycleEvents')->where(['domain' => 'this.wikibase.cloud'])->first()->wikiLifecycleEvents()->first();
        $this->assertNull($events['first_edited']);
        $this->assertNull($events['last_edited']);
    }
}
