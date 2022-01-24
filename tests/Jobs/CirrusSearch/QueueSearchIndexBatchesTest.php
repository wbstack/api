<?php

namespace Tests\Jobs\CirrusSearch;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Jobs\CirrusSearch\ElasticSearchIndexInit;
use App\Http\Curl\HttpRequest;
use App\WikiManager;
use App\WikiSetting;
use App\Wiki;
use Illuminate\Contracts\Queue\Job;
use App\WikiDb;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\RuntimeException;
use App\Jobs\CirrusSearch\QueueSearchIndexBatches;
use App\Jobs\CirrusSearch\ForceSearchIndex;
use Illuminate\Support\Facades\Bus;
use Queue;

class QueueSearchIndexBatchesTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;

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
        Queue::fake();
        
        $mockResponse = [
            'warnings' => [],
            'wbstackQueueSearchIndexBatches' => [
                "return" => 0,
                "output" => [
                    "php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 0 --toId 1000",
                    "php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 1001 --toId 1234",
                    "php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 1235 --toId 1236",
                ]
            ]
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $request->expects($this->once())
            ->method('setOptions')
            ->with([
                CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackQueueSearchIndexBatches&format=json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 1000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: '.$this->wiki->domain,
                ]
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();

        $job = new QueueSearchIndexBatches($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle($request);

        Queue::assertPushed(function (ForceSearchIndex $job) {
            return $job->wikiId() === $this->wiki->id
            && $job->fromId() === 0 && $job->toId() === 1000;
        });

        Queue::assertPushed(function (ForceSearchIndex $job) {
            return $job->wikiId() === $this->wiki->id
            && $job->fromId() === 1001 && $job->toId() === 1234;
        });

        Queue::assertPushed(function (ForceSearchIndex $job) {
            return $job->wikiId() === $this->wiki->id
            && $job->fromId() === 1235 && $job->toId() === 1236;
        });

    }
}
