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
use ErrorException;

class SetWikiLogoTest extends TestCase
{
    use DatabaseTransactions;
    use DispatchesJobs;

    private function assertJobFails(string $wikiKey, string $wikiValue, string $logoPath)
    {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->once())
            ->method('fail');
        $this->dispatchNow($job);
    }

    private function assertJobSucceeds(string $wikiKey, string $wikiValue, string $logoPath)
    {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $this->dispatchNow($job);
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testSetLogoFails( $wikiKey, $wikiValue, $logoPath )
    {
        $storage = Storage::fake('gcs-public-static');
        $this->assertJobFails($wikiKey, $wikiValue, $logoPath);
        $this->assertFalse($storage->exists('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/raw.png'));
    }

    /**
     * @dataProvider validProvider
     */
    public function testSetLogoSucceeds( $wikiKey, $wikiValue, $logoPath )
    {
        $wiki = Wiki::firstWhere($wikiKey, $wikiValue);
        $storage = Storage::fake('gcs-public-static');

        // get the previous logo and favicon settings
        try {
            $previousLogoSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value);
        } catch (ErrorException $e) {
            $previousLogoSettingURL = null;
        }
        try {
            $previousFaviconSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgFavicon])->value);
        } catch (ErrorException $e) {
            $previousFaviconSettingURL = null;
        }

        // run the job and assert it succeeds
        $this->assertJobSucceeds($wikiKey, $wikiValue, $logoPath);

        // check logo is uploaded
        $this->assertTrue($storage->exists('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/raw.png'));
        // check logo resized to 135
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png'));
        $this->assertSame(135, $logo->height());
        $this->assertSame(135, $logo->width());
        // check favicon resized to 64
        $logo = Image::make($storage->path('sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico'));
        $this->assertSame(64, $logo->height());
        $this->assertSame(64, $logo->width());

        // get the current logo and favicon settings
        $currentLogoSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value);
        $currentFaviconSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgFavicon])->value);

        // check the settings have been updated
        $this->assertNotEquals($previousLogoSettingURL, $currentLogoSettingURL);
        $this->assertNotEquals($previousFaviconSettingURL, $currentFaviconSettingURL);

        // check the URL paths are correct
        $this->assertSame('/storage/sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/135.png', $currentLogoSettingURL['path']);
        $this->assertSame('/storage/sites/bc7235a51e34c1d3ebfddeb538c20c71/logos/64.ico', $currentFaviconSettingURL['path']);
    }

    public function validProvider()
    {
        return [
            # $wikiKey, $wikiValue, $logoPath
            ['id', 1, __DIR__ . "/../Data/logo_200x200.png"],
            ['domain', 'seededsite.nodomain.dev', __DIR__ . "/../Data/logo_200x200.png" ]
        ];
    }

    public function invalidProvider()
    {
        # $wikiKey, $wikiValue, $logoPath
        return [
            # id doesn't exist
            ['id', 999, __DIR__ . "/../Data/logo_200x200.png"],
            # logo path doesn't exist
            ['id', 1, "/invalid/logo/path.png"],
            # domain doesn't exist
            ['domain', 'non.existant.dev', __DIR__ . "/../Data/logo_200x200.png" ],
            # invalid key
            ['wikiid', 1, __DIR__ . "/../Data/logo_200x200.png"],
        ];
    }
}
