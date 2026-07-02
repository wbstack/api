<?php

namespace Tests\Jobs\CirrusSearch;

use App\Http\Curl\HttpRequest;
use App\Jobs\CirrusSearch\ForceSearchIndex;
use App\Services\MediaWikiHostResolver;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Queue;
use Tests\TestCase;

class ForceSearchIndexTest extends TestCase {
    use DatabaseTransactions;
    use DispatchesJobs;

    private $wiki;

    private $wikiDb;

    private $user;

    private $mwBackendHost;

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

        $toId = 10;
        $fromId = 0;

        $mockResponse = [
            'warnings' => [],
            'wbstackForceSearchIndex' => [
                'return' => 0,
                'output' => [
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 10 at 2/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 20 at 4/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 30 at 6/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 40 at 7/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 50 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 60 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 70 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 80 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 90 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 100 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 110 at 9/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 120 at 10/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 130 at 10/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 140 at 10/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 150 at 10/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 160 at 10/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 170 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 180 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 190 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 200 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 210 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 10 pages ending at 220 at 11/second',
                    '[mwdb_febf08b4c7-mwt_f66e770e74_] Indexed 9 pages ending at 229 at 11/second',
                    'Indexed a total of 229 pages at 11/second',
                ],
            ],
        ];

        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn(json_encode($mockResponse));

        $request->expects($this->once())
            ->method('setOptions')
            ->with([
                CURLOPT_URL => $this->mwBackendHost . '/w/api.php?action=wbstackForceSearchIndex&format=json&fromId=' . $fromId . '&toId=' . $toId,
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

        $job = new ForceSearchIndex('id', $this->wiki->id, $fromId, $toId);
        $job->setJob($mockJob);
        $job->handle($request, $this->mockMwHostResolver);
    }
}
