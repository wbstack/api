<?php

namespace Tests\Jobs;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Wiki;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\KubernetesIngressCreate;
use Maclof\Kubernetes\Client;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class KubernetesIngressCreateTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreateIngressJobDoesNotFail()
    {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => null ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new KubernetesIngressCreate( $wiki->id, "example.com" );
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode([ 'items' => [] ]) ),
            new Response(201, [], json_encode([]))
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
