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

class ElasticSearchIndexDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private $wiki;
    private $user;

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
    }

    public function testDeletesElasticSearchIndex()
    {
        $wikiBaseName = 'site1.localhost';

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
                CURLOPT_URL => getenv('ELASTICSEARCH_HOST').'/_cat/indices/'.$this->wiki->domain.'*?v&s=index&h=index',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]],
            [[
                CURLOPT_URL => getenv('ELASTICSEARCH_HOST').'/'.$this->wiki->domain.'*',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ]]);
        

        $job = new ElasticSearchIndexDelete($this->wiki->domain, $this->wiki->id, $request);
        $job->handle();

        // feature should get disabled
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => false])->count()
        );
    }

}
