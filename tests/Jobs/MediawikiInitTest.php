<?php

namespace Tests\Jobs;

use App\Http\Curl\HttpRequest;
use App\Jobs\MediawikiInit;
use App\Services\MediaWikiHostResolver;
use Illuminate\Contracts\Queue\Job;
use Tests\TestCase;

class MediawikiInitTest extends TestCase {
    private $wikiDomain;

    private $email;

    private $username;

    private $mwBackendHost;

    protected function setUp(): void {
        parent::setUp();
        $this->wikiDomain = 'some.domain.com';
        $this->username = 'username';
        $this->email = 'some@email.com';
        $this->mwBackendHost = 'mediawiki.localhost';
    }

    private function getMockRequest(string $mockResponse): HttpRequest {
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn($mockResponse);

        $request->expects($this->once())
            ->method('setOptions')
            ->with(
                [
                    CURLOPT_URL => $this->mwBackendHost . '/w/api.php?action=wbstackInit&format=json',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => http_build_query([
                        'username' => $this->username,
                        'email' => $this->email,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'content-type: application/x-www-form-urlencoded',
                        'host: ' . $this->wikiDomain,
                    ],
                ]
            );

        return $request;
    }

    private function getMockMwHostResolver() {
        $mwHostResolver = $this->createMock(MediaWikiHostResolver::class);
        $mwHostResolver->method('getBackendHostForDomain')->willReturn(
            $this->mwBackendHost
        );

        return $mwHostResolver;
    }

    public function testSuccess() {
        $mockHostResolver = $this->getMockMwHostResolver();

        $mockResponse = [
            'warnings' => [],
            'wbstackInit' => [
                'success' => 1,
                'output' => [],
            ],
        ];

        $mockResponseString = json_encode($mockResponse);
        $request = $this->getMockRequest($mockResponseString);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
            ->method('fail')
            ->withAnyParameters();

        $job = new MediawikiInit($this->wikiDomain, $this->username, $this->email);
        $job->setJob($mockJob);
        $job->handle($request, $mockHostResolver);
    }

    public function testFatalErrorIsHandled() {
        $mockHostResolver = $this->getMockMwHostResolver();

        $mockResponse = 'oh no';
        $request = $this->getMockRequest($mockResponse);

        $expectedExceptionMessage = 'wbstackInit call for some.domain.com. No wbstackInit key in response: ' . $mockResponse;

        $mockJob = $this->createMock(Job::class);

        $job = new MediawikiInit($this->wikiDomain, $this->username, $this->email);
        $job->setJob($mockJob);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $job->handle($request, $mockHostResolver);
    }
}
