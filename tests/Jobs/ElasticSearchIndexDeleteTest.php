<?php

namespace Tests\Jobs;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use App\WikiManager;
use App\WikiSetting;
use App\Wiki;
use App\Jobs\ElasticSearchIndexDelete;
use App\WikiDb;
use Illuminate\Contracts\Queue\Job;

class ElasticSearchIndexDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private $wiki;
    private $user;
    private $wikiDb;

    public function setUp(): void {
        parent::setUp();

        $this->user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $this->user->id]);
        WikiSetting::factory()->create(
            [
                'wiki_id' => $this->wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => true
            ]
        );
        $this->wikiDb = WikiDb::factory()->create([
            'wiki_id' => $this->wiki->id
        ]);
    }

    public function testDeletesElasticSearchIndex()
    {
        $this->wiki->delete();
        $wikiBaseName = $this->wikiDb->name;

        $mockResponse = "index\n" . "{$wikiBaseName}_content_blabla\n" . "{$wikiBaseName}_general_bla\n";
        $mockJsonSuccess = '{"acknowledged" : true}';

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls($mockResponse, $mockJsonSuccess);

        $request->expects($this->exactly(2))
            ->method('setOptions')
            ->withConsecutive( 
            [[
                CURLOPT_URL => getenv('ELASTICSEARCH_HOST').'/_cat/indices/'.$this->wikiDb->name.'*?v&s=index&h=index',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]],
            [[
                CURLOPT_URL => getenv('ELASTICSEARCH_HOST').'/'.$this->wikiDb->name.'*',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ]]);
        

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new ElasticSearchIndexDelete( $this->wiki->id );
        $job->setJob($mockJob);
        $job->handle( $request );

        // feature should get disabled
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => false])->count()
        );
    }

    /**
	 * @dataProvider failureProvider
	 */
    public function testFailure( $request, string $expectedFailure, $mockResponse, $settingStateInDatabase, $deleteWiki = true )
    {
        if ( $deleteWiki ) {
            $this->wiki->delete();
        }

        $expectedFailure = str_replace('<WIKI_ID>', $this->wiki->id, $expectedFailure);
        $expectedFailure = str_replace('<WIKI_DB_NAME>', $this->wikiDb->name, $expectedFailure);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
                ->method('fail')
                ->with(new \RuntimeException($expectedFailure));

        $request->method('execute')
            ->willReturnOnConsecutiveCalls( ...$mockResponse );

        $job = new ElasticSearchIndexDelete($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle( $request );
        
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => $settingStateInDatabase])->count()
        );

    }

    public function failureProvider() {

        $mockResponse = "index\n" . "some_index_content_blabla\n" . "some_index_general_bla\n";
        $mockJsonSuccess = '{"acknowledged" : true}';
        $mockFailure = '{"acknowledged" : false}';

        yield [
            $this->createMock(HttpRequest::class),
            'ElasticSearchIndexDelete job for <WIKI_ID> was not successful: {"acknowledged" : false}',
            [ $mockResponse, $mockFailure ],
            true
        ];

        yield [
            $this->createMock(HttpRequest::class),
            'ElasticSearchIndexDelete job for <WIKI_ID> was not successful: <html>',
            [ $mockResponse, '<html>' ],
            true
        ];

        if ( getenv('ELASTICSEARCH_HOST') ) {
            yield [
                $this->createMock(HttpRequest::class),
                'Response looks weird when querying http://'.getenv('ELASTICSEARCH_HOST').'/_cat/indices/<WIKI_DB_NAME>*?v&s=index&h=index',
                [ "<html>asdasd\n\nasdasd", $mockJsonSuccess ],
                true
            ];
        }

        yield [
            $this->createMock(HttpRequest::class),
            'No index to remove for <WIKI_ID>',
            ["index\n", $mockJsonSuccess ],
            false
        ];

        yield [
            $this->createMock(HttpRequest::class),
            'ElasticSearchIndexDelete job for <WIKI_ID> but that wiki is not marked as deleted.',
            [$mockResponse, $mockJsonSuccess ],
            true,
            false // will not delete the wiki
        ];
    }

}
