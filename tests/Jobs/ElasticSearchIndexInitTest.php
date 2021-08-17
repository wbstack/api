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

class ElasticSearchIndexInitTest extends TestCase
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
                'value' => false
            ]
        );
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

        $job = new ElasticSearchIndexInit($this->wiki->domain, $this->wiki->id, $request);
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
                    "\t\tValidating {$this->wiki->domain}_general alias...ok"
                ]
            ]
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $job = new ElasticSearchIndexInit($this->wiki->domain, $this->wiki->id, $request);
        $job->handle();

        // feature should get enabled
        $this->assertSame(
             1, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->count()
        );
    }

    public function testFailure()
    {
        $mockResponse = [
            'warnings' => [],
            'wbstackElasticSearchInit' => [
                "success" => 0,
                "output" => []
            ]
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $job = new ElasticSearchIndexInit($this->wiki->domain, $this->wiki->id, $request);
        $job->handle();

        // feature should not get enabled
        $this->assertSame(
             0, 
             WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->count()
        );
    }

    public function testJobTriggeredButNoSetting()
    {
        WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch ])->first()->delete();
        $request = $this->createMock(HttpRequest::class);
        $request->expects( $this->never() )->method('execute');

        $job = new ElasticSearchIndexInit($this->wiki->domain, $this->wiki->id, $request);
        $job->handle();
    }
}
