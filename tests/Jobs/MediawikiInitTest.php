<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use Illuminate\Contracts\Queue\Job;
use PHPUnit\TextUI\RuntimeException;
use App\Jobs\MediawikiInit;

class MediawikiInitTest extends TestCase
{

    private $wikiDomain;
    private $email;
    private $username;

    public function setUp(): void {
        parent::setUp();
        $this->wikiDomain = "some.domain.com";
        $this->username = "username";
        $this->email = "some@email.com";
    }

    private function getMockRequest( string $mockResponse ): HttpRequest {
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn($mockResponse);

        $request->expects($this->once())
            ->method('setOptions')
            ->with(
                [
                    CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackInit&format=json',
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
                        'host: '.$this->wikiDomain,
                    ],
                ]
            );

        return $request;
    }

    public function testSuccess()
    {
        $mockResponse = [
            'warnings' => [],
            'wbstackInit' => [
                "success" => 1,
                "output" => []
            ]
        ];

        $mockResponseString = json_encode($mockResponse);
        $request = $this->getMockRequest( $mockResponseString );

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();

        $job = new MediawikiInit( $this->wikiDomain, $this->username, $this->email );
        $job->setJob($mockJob);
        $job->handle($request);
    }

    public function testFatalErrorIsHandled()
    {
        $mockResponse = 'oh no';
        $request = $this->getMockRequest($mockResponse);

        $expectedExceptionMessage = 'wbstackInit call for some.domain.com. No wbstackInit key in response: ' . $mockResponse;

        $mockJob = $this->createMock(Job::class);

        $job = new MediawikiInit( $this->wikiDomain, $this->username, $this->email );
        $job->setJob($mockJob);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $job->handle($request);
    }
}
