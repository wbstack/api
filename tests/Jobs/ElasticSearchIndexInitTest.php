<?php

namespace Tests\Jobs;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Jobs\ElasticSearchIndexInit;
use App\Http\Curl\HttpRequest;
use App\WikiManager;
use App\WikiSetting;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;
use App\WikiDb;

class ElasticSearchIndexInitTest extends TestCase
{
    use DatabaseTransactions;

    private $wiki;
    private $wikiDb;
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
        $this->wikiDb = WikiDb::factory()->create([
            'wiki_id' => $this->wiki->id
        ]);
    }

    public function testSuccess()
    {
        $mockResponse = [
            'warnings' => [],
            'wbstackElasticSearchInit' => [
                "success" => 1,
                "output" => [
                    "\tCreating index...ok" // successfully created some index
                ]
            ]
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();

        $job = new ElasticSearchIndexInit($this->wiki->id, $request);
        $job->setJob($mockJob);
        $job->handle();

        // feature should get enabled
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->count()
        );
    }

    public function testUpdate()
    {
        $mockResponse = [
            'warnings' => [],
            'wbstackElasticSearchInit' => [
                "success" => 1,
                "output" => [
                    "\t\tValidating {$this->wikiDb->name}_general alias...ok"
                ]
            ]
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();

        $job = new ElasticSearchIndexInit($this->wiki->id, $request);
        $job->setJob($mockJob);
        $job->handle();

        // feature should get enabled
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->count()
        );
    }

    public function testJobTriggeredButNoSetting()
    {
        WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch ])->first()->delete();
        $request = $this->createMock(HttpRequest::class);
        $request->expects( $this->never() )->method('execute');

        $job = new ElasticSearchIndexInit($this->wiki->id, $request);
        $job->handle();
    }

    /**
	 * @dataProvider failureProvider
	 */
    public function testFailure( $request, string $expectedFailure, $mockResponse )
    {

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
                ->method('fail')
                ->with(new \RuntimeException(str_replace('<WIKI_ID>', $this->wiki->id, $expectedFailure)));
                
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $job = new ElasticSearchIndexInit($this->wiki->id, $request);
        $job->setJob($mockJob);
        $job->handle();
        

        $this->assertSame(
             0, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->count()
        );
    }

    public function failureProvider() {

        $mockResponse = [];
        yield [
            $this->createMock(HttpRequest::class),
            'wbstackElasticSearchInit call for <WIKI_ID>. No wbstackElasticSearchInit key in response: []',
            $mockResponse
        ];

        $mockResponse = [
            'warnings' => [],
            'wbstackElasticSearchInit' => [
                "success" => 0,
                "output" => []
            ]
        ];

        yield [
            $this->createMock(HttpRequest::class),
            'wbstackElasticSearchInit call for <WIKI_ID> was not successful:{"warnings":[],"wbstackElasticSearchInit":{"success":0,"output":[]}}',
            $mockResponse
        ];

        $curlError = $this->createMock(HttpRequest::class);
        $curlError->method('error')->willReturn('Scary Error!');
        yield [
            $curlError,
            'curl error for <WIKI_ID>: Scary Error!',
            $mockResponse
        ];

        $mockResponse['wbstackElasticSearchInit']['success'] = 1;
        yield [
            $this->createMock(HttpRequest::class),
            'wbstackElasticSearchInit call for <WIKI_ID> was not successful:{"warnings":[],"wbstackElasticSearchInit":{"success":1,"output":[]}}',
            $mockResponse
        ];

    }

}
