<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use App\Jobs\DeleteQueryserviceNamespaceJob;
use App\QueryserviceNamespace;
use Illuminate\Support\Facades\DB;
use App\WikiManager;
use App\WikiSetting;
use App\User;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\SetWikiLogo;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


class SetWikiLogoTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;

    public function assertJobFails(string $wikiKey, string $wikiValue, string $logoPath)
    {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->once())
            ->method('fail');
        $this->dispatchNow($job);
    }

    public function assertJobSucceeds(string $wikiKey, string $wikiValue, string $logoPath)
    {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $this->dispatchNow($job);
    }

    public function testInvalidLogoPath()
    {
        $this->assertJobFails('id', 1, "/path/to/logo");
    }

    public function testValidLogoPath()
    {
        $wikiId = 1;
        $storage = Storage::disk('gcs-public-static');
        // delete the raw.png file form the cloud storage
        $storage->delete('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/raw.png');
        $this->assertJobSucceeds('id', $wikiId, __DIR__ . "/logo_200x200.png");
        $this->assertTrue($storage->exists('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/raw.png'));
    }

    public function testLogoResize135()
    {
        $storage = Storage::disk('gcs-public-static');
        // delete the 135.png file form the cloud storage
        $storage->delete('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png');
        
        $this->assertJobSucceeds('id', 1, __DIR__ . "/logo_200x200.png");
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png'));
        $this->assertSame(135, $logo->height());
        $this->assertSame(135, $logo->width());

    }

    public function testLogoResize64()
    {
        $storage = Storage::disk('gcs-public-static');
        // delete the 64.ico file form the cloud storage
        $storage->delete('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico');

        $this->assertJobSucceeds('id', 1, __DIR__ . "/logo_200x200.png");
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico'));
        $this->assertSame(64, $logo->height());
        $this->assertSame(64, $logo->width());
    }

    public function testSettingsSet()
    {
        $storage = Storage::disk('gcs-public-static');
        // delete the 135.png file form the cloud storage
        $storage->delete('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png');

        $this->assertJobSucceeds('id', 1, __DIR__ . "/logo_200x200.png");
        $wikiSetting = WikiSetting::firstWhere('id', 1);
        var_dump($wikiSetting); die;
        $this->assertSame($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png'), $wikiSetting->wgLogo);
    }
}
