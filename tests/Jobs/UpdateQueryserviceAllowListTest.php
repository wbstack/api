<?php

namespace Tests\Jobs;

use App\Jobs\UpdateQueryserviceAllowList;
use App\Wiki;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maclof\Kubernetes\Client;
use Tests\TestCase;

class UpdateQueryserviceAllowListTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        Wiki::factory()->create(['domain' => 'somesite-5.localhost']);
        Wiki::factory()->create(['domain' => 'somesite-6.localhost']);
    }

    public function testMissingConfigMapFailure(): void {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
            ->method('fail')
            ->with(new \RuntimeException(
                "Queryservice config map 'queryservice-allowlist' does not exist."
            ));

        $job = new UpdateQueryserviceAllowList;
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => []])),
            new Response(200, [], json_encode(['items' => []])),
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

    public function testSuccess(): void {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new UpdateQueryserviceAllowList;
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => [[
                'kind' => 'ConfigMap',
                'apiVersion' => 'v1',
                'data' => [
                    'allowlist-static.txt' => implode(PHP_EOL, [
                        'https://somesite-1.localhost/query/sparql',
                        'https://somesite-2.localhost/query/sparql',
                    ]),
                    'allowlist.txt' => implode(PHP_EOL, [
                        'https://somesite-3.localhost/query/sparql',
                        'https://somesite-4.localhost/query/sparql',
                    ]),
                ],
            ]]])),
            new Response(200, [], json_encode(['items' => []])),
        ]);

        $requests = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($requests));
        $mockGuzzle = GuzzleClient::createWithConfig([
            'handler' => $handlerStack,
            'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
        ]);

        $job->handle(new Client([
            'master' => 'https://kubernetes.default.svc',
            'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ], null, $mockGuzzle));

        $this->assertCount(2, $requests);
        $this->assertEquals(
            [
                'kind' => 'ConfigMap',
                'apiVersion' => 'v1',
                'data' => [
                    'allowlist-static.txt' => implode(PHP_EOL, [
                        'https://somesite-1.localhost/query/sparql',
                        'https://somesite-2.localhost/query/sparql',
                    ]),
                    'allowlist.txt' => implode(PHP_EOL, [
                        'https://somesite-5.localhost/query/sparql',
                        'https://somesite-6.localhost/query/sparql',
                        'https://somesite-1.localhost/query/sparql',
                        'https://somesite-2.localhost/query/sparql',
                    ]),
                ],
            ],
            json_decode($requests[1]['request']->getBody(), true)
        );
    }
}
