<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use Illuminate\Contracts\Queue\Job;
use PHPUnit\TextUI\RuntimeException;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\Jobs\SiteStatsUpdateJob;

class SiteStatsUpdateJobTest extends TestCase
{

    public function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create();
        $this->manager = WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $this->user->id]);
    }

    private function getMockRequest( string $mockResponse ): HttpRequest {
        $request = $this->createMock(HttpRequest::class);
        $request->method('execute')->willReturn($mockResponse);

        $request->expects($this->once())
            ->method('setOptions')
            ->with(
                [
                    CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackSiteStatsUpdate&format=json',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_TIMEOUT => 60*5,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => [
                        'content-type: application/x-www-form-urlencoded',
                        'host: '.$this->wiki->domain,
                    ],
                ]
            );

        return $request;
    }

    public function testSuccess()
    {
        $mockResponse = [
            'warnings' => [],
            'wbstackSiteStatsUpdate' => [
                "return" => 0
            ]
        ];

        $mockResponseString = json_encode($mockResponse);
        $request = $this->getMockRequest( $mockResponseString );

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();

        $job = new SiteStatsUpdateJob( $this->wiki->id );
        $job->setJob($mockJob);
        $job->handle($request);
    }

    public function testFatalErrorIsHandled()
    {
        $mockResponse = [
            'wbstackSiteStatsUpdate' => [
                "return" => 1
            ]
        ];

        $mockResponseString = json_encode($mockResponse);
        $request = $this->getMockRequest($mockResponseString);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
                ->method('fail')
                ->with(new \RuntimeException('wbstackSiteStatsUpdate call for ' . $this->wiki->domain . ' was not successful: ' . $mockResponseString ));

        $job = new SiteStatsUpdateJob( $this->wiki->id );
        $job->setJob($mockJob);
        $job->handle($request);
    }
}
