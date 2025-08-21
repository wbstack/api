<?php

namespace Tests\Jobs;

use App\Jobs\ProcessMediaWikiJobsJob;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maclof\Kubernetes\Client;
use Tests\TestCase;

class ProcessMediaWikiJobsJobTest extends TestCase {
    use RefreshDatabase;

    public function testJobFailOnNoMediaWikiPod() {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())->method('fail');

        $job = new ProcessMediaWikiJobsJob('test.wikibase.cloud');
        $job->setJob($mockJob);

        $mock = new MockHandler([
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

    public function testJobDoesNotFail() {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new ProcessMediaWikiJobsJob('test.wikibase.cloud');
        $job->setJob($mockJob);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => [
                [
                    'kind' => 'Pod',
                    'spec' => [
                        'containers' => [
                            [
                                'image' => 'helloworld',
                                'env' => [
                                    'SOMETHING' => 'something',
                                ],
                            ],
                        ],
                    ],
                ],
            ]])),
            new Response(200, [], json_encode(['items' => []])),
            new Response(201, [], json_encode([
                'metadata' => [
                    'name' => 'some-job-name',
                ],
            ])),
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
