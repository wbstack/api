<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Wiki;
use App\WikiEntityImport;
use App\WikiEntityImportStatus;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\WikiEntityImportJob;
use Maclof\Kubernetes\Client;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class WikiEntityImportJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiEntityImport::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiEntityImport::query()->delete();
        parent::tearDown();
    }

    public function testJobDoesNotFail()
    {

        Http::fake([
            'mediawiki-139-app-backend.default.svc.cluster.default/w/api.php?action=wbstackPlatformOauthGet&format=json' =>
                Http::response([
                    'wbstackPlatformOauthGet' => [
                        'success' => '1',
                        'data' => [
                            'consumerKey' => 'aaa',
                            'consumerSecret' => 'bbb',
                            'accessKey' => 'yyy',
                            'accessSecret' => 'zzz',
                        ],
                    ],
                ], 200),
        ]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $import = WikiEntityImport::factory()->create([
            'wiki_id' => $wiki->id,
            'status' => WikiEntityImportStatus::Pending,
        ]);

        $job = new WikiEntityImportJob(
            wikiId: $wiki->id,
            sourceWikiUrl: 'https://www.wikidata.org',
            entityIds: ['Q1', 'Q42'],
            importId: $import->id,
        );
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => []])),
            new Response(201, [], json_encode([
                'kind' => 'Job',
                'metadata' => [
                    'name' => 'some-job-name'
                ],
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $mockGuzzle = GuzzleClient::createWithConfig([
            'handler' => $handlerStack,
            'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
        ]);

        $job->handle(new Client([
            'master' => 'https://kubernetes.default.svc',
            'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ], null, $mockGuzzle));
    }
}
