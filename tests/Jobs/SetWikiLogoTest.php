<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use App\Jobs\DeleteQueryserviceNamespaceJob;
use App\QueryserviceNamespace;
use Illuminate\Support\Facades\DB;
use App\WikiManager;
use App\User;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\SetWikiLogo;
use Illuminate\Foundation\Bus\DispatchesJobs;

class SetWikiLogoTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;
    
    public function assertJobFails(string $wikiKey, string $wikiValue, string $logoPath) {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->once())
            ->method('fail');
        $this->dispatchNow($job);
    }

    public function assertJobSucceeds(string $wikiKey, string $wikiValue, string $logoPath) {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $this->dispatchNow($job);
    }

    public function testSetLogoInvalid()
    {
        $this->assertJobFails( 'id', 1, "/path/to/logo" );
    }
    
    public function testSetLogoValid()
    {
        $this->assertJobSucceeds('id', 1, __DIR__ . "/logo_200x200.png");
    }
}
