<?php

namespace Tests\Jobs;

use App\WikiSetting;
use App\Wiki;
use App\Jobs\SetWikiLogo;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;


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

    public function provider()
    {
        return [
            # $wikiKey, $wikiValue, $logoPath
            ['id', 1, __DIR__ . "/logo_200x200.png"],
            ['domain', 'seededsite.nodomain.dev', __DIR__ . "/logo_200x200.png" ]
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testValidLogo( $wikiKey, $wikiValue, $logoPath )
    {
        $wiki = Wiki::firstWhere($wikiKey, $wikiValue);
        $storage = Storage::disk('gcs-public-static');
        // clean up the logos directory
        $storage->deleteDirectory('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos');
        
        $this->assertJobSucceeds($wikiKey, $wikiValue, $logoPath);
        $this->assertTrue($storage->exists('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/raw.png'));
        // check logo resized to 135
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png'));
        $this->assertSame(135, $logo->height());
        $this->assertSame(135, $logo->width());
        // check favicon resized to 64
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico'));
        $this->assertSame(64, $logo->height());
        $this->assertSame(64, $logo->width());

        // get the wgLogo setting
        $wgLogoSetting = $wiki->settings()->where(['name' => WikiSetting::wgLogo])->first()->value;
        $wgFaviconSetting = $wiki->settings()->where(['name' => WikiSetting::wgFavicon])->first()->value;
        
        # TODO: mock out the call to time() in the unit under test?
        $expectedLogoURL = $storage->url('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png') . '?u=' . time();
        $expectedFaviconURL = $storage->url('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico') . '?u=' . time();

        $this->assertSame($expectedLogoURL, $wgLogoSetting);
        $this->assertSame($expectedFaviconURL, $wgFaviconSetting);
    }

}
