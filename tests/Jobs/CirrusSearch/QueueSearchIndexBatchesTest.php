<?php

namespace Tests\Jobs\CirrusSearch;

use App\Http\Curl\HttpRequest;
use App\Jobs\CirrusSearch\ForceSearchIndex;
use App\Jobs\CirrusSearch\QueueSearchIndexBatches;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use App\WikiSetting;
use App\Services\MediaWikiHostResolver;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Queue;
use Tests\TestCase;

class QueueSearchIndexBatchesTest extends TestCase {
    use DatabaseTransactions;
    use DispatchesJobs;

    private $wiki;

    private $wikiDb;

    private $user;

    private $mockMwHostResolver;

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

        $this->mwBackendHost = 'mediawiki.localhost';

        $this->mockMwHostResolver = $this->createMock(MediaWikiHostResolver::class);
        $this->mockMwHostResolver->method('getBackendHostForDomain')->willReturn(
            $this->mwBackendHost
        );
    }

    public function testSuccess() {
        Queue::fake();

        $mockResponse = [
            'warnings' => [],
            'wbstackQueueSearchIndexBatches' => [
                'return' => 0,
                'output' => [
                    'php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 0 --toId 1000',
                    'php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 1001 --toId 1234',
                    'php /var/www/html/w/extensions/CirrusSearch/maintenance/ForceSearchIndex.php --queue 1 --skipLinks 1 --indexOnSkip 1 --fromId 1235 --toId 1236',
                ],
            ],
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $request->expects($this->once())
            ->method('setOptions')
            ->with([
                CURLOPT_URL => $this->mwBackendHost . '/w/api.php?action=wbstackQueueSearchIndexBatches&format=json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 1000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: ' . $this->wiki->domain,
                ],
            ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
            ->method('fail')
            ->withAnyParameters();

        $job = new QueueSearchIndexBatches($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle($request, $this->mockMwHostResolver);

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

    public function testMediawikiErrorResponse() {
        $errorResponse = file_get_contents(
            __DIR__ . '/../../data/mediawiki-api-error-response.json'
        );

        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn($errorResponse);

        $request->expects($this->once())
            ->method('setOptions')
            ->with([
                CURLOPT_URL => $this->mwBackendHost . '/w/api.php?action=wbstackQueueSearchIndexBatches&format=json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 1000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: ' . $this->wiki->domain,
                ],
            ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
            ->method('fail')
            ->with(new \RuntimeException('wbstackQueueSearchIndexBatches call failed with api error: Unrecognized value for parameter "action": wbstackQueueSearchIndexBatches.'));

        $job = new QueueSearchIndexBatches($this->wiki->id);
        $job->setJob($mockJob);
        $job->handle($request, $this->mockMwHostResolver);
    }
}
