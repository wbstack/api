<?php

namespace Tests\Jobs;

use App\Wiki;
use App\Jobs\UpdateWikiSiteStatsJob;
use Tests\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;

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
            $responses = [
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => [
                    'this.wikibase.cloud' => Http::response(['query' => [
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
                    ]]),
                    'that.wikibase.cloud' => Http::response(['query' => [
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
                    ]])
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=newer' => [
                    'this.wikibase.cloud' => Http::response([
                        'query' => [
                            'allrevisions' => [
                                [
                                    'revisions' => [
                                        [
                                            'revid' => 2
                                        ]
                                    ]
                                ]
                            ]
                        ]
                        ]),
                        'that.wikibase.cloud' => Http::response([
                        'query' => [
                            'allrevisions' => [
                                [
                                    'revisions' => [
                                        [
                                            'revid' => 2
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&prop=revisions&rvprop=timestamp&revids=2' => [
                    'this.wikibase.cloud' => Http::response([
                        'query' => [
                            'pages' => [
                                [
                                    'revisions' => [
                                        [
                                            'timestamp' => '2023-02-27T16:57:06Z'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                    'that.wikibase.cloud' => Http::response([
                        'query' => [
                            'pages' => [
                                [
                                    'revisions' => [
                                        [
                                            'timestamp' => '2023-05-07T21:31:47Z'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=recentchanges&format=json' => [
                    'this.wikibase.cloud' => Http::response([
                        'query' => [
                            'recentchanges' => [
                                [
                                    'type' => 'new',
                                    'ns' => 120,
                                    'title' => 'Item:Q312951',
                                    'pageid' => 310675,
                                    'revid' => 830699,
                                    'old_revid' => 0,
                                    'rcid' => 829604,
                                    'timestamp' => '2023-09-16T17:22:33Z'
                                ],
                                [

                                    'type' => 'edit',
                                    'ns' => 4,
                                    'title' => 'Project:Home',
                                    'pageid' => 1,
                                    'revid' => 830698,
                                    'old_revid' => 830094,
                                    'rcid' => 829603,
                                    'timestamp' => '2023-09-16T04:50:03Z'
                                ]
                            ]
                        ]
                    ]),
                    'that.wikibase.cloud' => Http::response([
                        'query' => [
                            'recentchanges' => [
                                [
                                    'type' => 'edit',
                                    'ns' => 120,
                                    'title' => 'Item:Q158700',
                                    'pageid' => 204252,
                                    'revid' => 438675,
                                    'old_revid' => 438674,
                                    'rcid' => 441539,
                                    'timestamp' => '2023-09-19T11:35:09Z'
                                ],
                                [
                                    'type' => 'edit',
                                    'ns' => 120,
                                    'title' => 'Item:Q158700',
                                    'pageid' => 204252,
                                    'revid' => 438674,
                                    'old_revid' => 438673,
                                    'rcid' => 441538,
                                    'timestamp' => '2023-09-19T11:33:50Z'
                                ]
                            ]
                        ]
                    ]),
                ],
            ];

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
        });

        $mockJob = $this->createMock(Job::class);
        $job = new UpdateWikiSiteStatsJob();
        $job->setJob($mockJob);

        $mockJob->expects($this->never())->method('fail');
        $mockJob->expects($this->never())->method('markAsFailed');
        $job->handle();

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
            $responses = [
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json' => [
                    'fail.wikibase.cloud' => Http::response('DINOSAUR OUTBREAK!', 500),
                    'incomplete.wikibase.cloud' => Http::response(['query' => [
                        'statistics' => [
                            'articles' => 99,
                            'not' => 129,
                            'sure' => 11,
                            'what' => 102,
                            'happened' => 20
                        ]
                    ]]),
                    'that.wikibase.cloud' => Http::response(['query' => [
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
                    ]])
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&list=allrevisions&formatversion=2&arvlimit=1&arvprop=ids&arvexcludeuser=PlatformReservedUser&arvdir=newer' => [
                    'fail.wikibase.cloud' => Http::response([]),
                    'incomplete.wikibase.cloud' => Http::response([
                        'query' => [
                            'allrevisions' => [
                                [
                                    'revisions' => [
                                        [
                                            'revid' => 1
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                    'that.wikibase.cloud' => Http::response([]),
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&prop=revisions&rvprop=timestamp&revids=1' => [
                    'fail.wikibase.cloud' => Http::response([]),
                    'incomplete.wikibase.cloud' => Http::response([
                        'query' => [
                            'pages' => [
                                [
                                    'revisions' => [
                                        [
                                            'timestamp' => '2023-05-07T21:31:47Z'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]),
                    'that.wikibase.cloud' => Http::response([]),
                ],
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=recentchanges&format=json' => [
                    'fail.wikibase.cloud' => Http::response([]),
                    'incomplete.wikibase.cloud' => Http::response('haha whoops', 500),
                    'that.wikibase.cloud' => Http::response([]),
                ],
            ];

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
        $events2 = Wiki::with('wikiLifecycleEvents')->where(['domain' => 'incomplete.wikibase.cloud'])->first()->wikiLifecycleEvents()->first();
        $this->assertEquals($events2['first_edited']->toIso8601String(), '2023-05-07T21:31:47+00:00');
        $this->assertEquals($events2['last_edited'], null);
    }
}
