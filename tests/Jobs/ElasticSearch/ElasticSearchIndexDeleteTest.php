<?php

namespace Tests\Jobs\ElasticSearch;

use App\Http\Curl\HttpRequest;
use App\Jobs\ElasticSearchIndexDelete;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ElasticSearchIndexDeleteTest extends TestCase {
    use DatabaseTransactions;

    private $wiki;

    private $user;

    private $wikiDb;

    protected function setUp(): void {
        parent::setUp();

        $this->user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $this->user->id]);
        WikiSetting::factory()->create(
            [
                'wiki_id' => $this->wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => true,
            ]
        );
        $this->wikiDb = WikiDb::factory()->create([
            'wiki_id' => $this->wiki->id,
        ]);
    }

    public function testDeletesElasticSearchIndex() {
        $this->wiki->delete();
        $wikiBaseName = $this->wikiDb->name;

        $mockResponse = "index\n" . "{$wikiBaseName}_content_blabla\n" . "{$wikiBaseName}_general_bla\n";
        $mockJsonSuccess = '{"acknowledged" : true}';

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls($mockResponse, $mockJsonSuccess);

        $request->expects($this->exactly(2))
            ->method('setOptions');

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new ElasticSearchIndexDelete($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle($request);

        // feature should get disabled
        $this->assertSame(
            1,
            WikiSetting::where(['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => false])->count()
        );
    }

    /**
     * @dataProvider failureProvider
     */
    public function testFailure(string $expectedFailure, $mockResponse, $settingStateInDatabase, $deleteWiki = true) {
        $request = $this->createMock(HttpRequest::class);

        if ($deleteWiki) {
            $this->wiki->delete();
        }

        $expectedFailure = str_replace('<WIKI_ID>', $this->wiki->id, $expectedFailure);
        $expectedFailure = str_replace('<WIKI_DB_NAME>', $this->wikiDb->name, $expectedFailure);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
            ->method('fail')
            ->with(new \RuntimeException($expectedFailure));

        $request->method('execute')
            ->willReturnOnConsecutiveCalls(...$mockResponse);

        $job = new ElasticSearchIndexDelete($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle($request);

        $this->assertSame(
            1,
            WikiSetting::where(['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => $settingStateInDatabase])->count()
        );

    }

    public static function failureProvider() {

        $mockResponse = "index\n" . "some_index_content_blabla\n" . "some_index_general_bla\n";
        $mockJsonSuccess = '{"acknowledged" : true}';
        $mockFailure = '{"acknowledged" : false}';

        $elasticSearchHost = 'localhost:9200'; // evil hardcoded value? previously read via Config::get('wbstack.elasticsearch_hosts'), but not possible anymore in static context

        yield [
            'ElasticSearchIndexDelete job for <WIKI_ID> was not successful: {"acknowledged" : false}',
            [$mockResponse, $mockFailure],
            true,
        ];

        yield [
            'ElasticSearchIndexDelete job for <WIKI_ID> was not successful: <html>',
            [$mockResponse, '<html>'],
            true,
        ];

        if ($elasticSearchHost) {
            yield [
                'Response looks weird when querying http://' . $elasticSearchHost . '/_cat/indices/<WIKI_DB_NAME>*?v&s=index&h=index',
                ["<html>asdasd\n\nasdasd", $mockJsonSuccess],
                true,
            ];
        }

        yield [
            'ElasticSearchIndexDelete job for <WIKI_ID> but that wiki is not marked as deleted.',
            [$mockResponse, $mockJsonSuccess],
            true,
            false, // will not delete the wiki
        ];
    }
}
