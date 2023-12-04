<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\SpawnQueryserviceUpdaterJob;
use Maclof\Kubernetes\Client;
use Http\Adapter\Guzzle6\Client as Guzzle6Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class SpawnQueryserviceUpdaterJobTest extends TestCase
{
    use RefreshDatabase;

    public function testJobFailOnNoUpdaterPod()
    {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())->method('fail');

        $job = new SpawnQueryserviceUpdaterJob(
            'test.wikibase.cloud',
            'Q1,P1,Q2,P2,Q3',
            'http://wdqs.svc:9999/bigdata/namespace/wdq/sparql',
        );
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode([ 'items' => [] ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $mockGuzzle = Guzzle6Client::createWithConfig([
            'handler' => $handlerStack,
            'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
        ]);

        $job->handle(new Client([
            'master' => 'https://kubernetes.default.svc',
            'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ], null, $mockGuzzle));
    }

    public function testJobDoesNotFail()
    {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new SpawnQueryserviceUpdaterJob(
            'test.wikibase.cloud',
            'Q1,P1,Q2,P2,Q3',
            'http://wdqs.svc:9999/bigdata/namespace/wdq/sparql',
        );
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode([ 'items' => [
                [
                    'kind' => 'Pod',
                    'spec' => [
                        'containers' => [
                            [
                                'image' => 'helloworld',
                                'env' => [
                                    'SOMETHING' => 'something'
                                ]
                            ]
                        ]
                    ]
                ]
            ]])),
            new Response(200, [], json_encode([ 'items' => [] ])),
            new Response(201, [], json_encode([
                'metadata' => [
                    'name' => 'some-job-name'
                ]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $mockGuzzle = Guzzle6Client::createWithConfig([
            'handler' => $handlerStack,
            'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
        ]);

        $job->handle(new Client([
            'master' => 'https://kubernetes.default.svc',
            'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ], null, $mockGuzzle));
    }
}
