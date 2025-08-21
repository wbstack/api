<?php

namespace Tests\Jobs;

use App\Jobs\KubernetesIngressCreate;
use App\User;
use App\Wiki;
use App\WikiManager;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maclof\Kubernetes\Client;
use Tests\TestCase;

class KubernetesIngressCreateTest extends TestCase {
    use DatabaseTransactions;

    public function testCreateIngressJobDoesNotFail() {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['deleted_at' => null]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new KubernetesIngressCreate($wiki->id, 'example.com');
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => []])),
            new Response(201, [], json_encode([])),
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
