<?php

namespace Tests\Jobs;

use App\Jobs\SetWikiLogo;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use ErrorException;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class SetWikiLogoTest extends TestCase {
    use DatabaseTransactions;
    use DispatchesJobs;

    private function assertJobFails(string $wikiKey, string $wikiValue, string $logoPath) {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->once())
            ->method('fail');
        $job->handle();
    }

    private function assertJobSucceeds(string $wikiKey, string $wikiValue, string $logoPath) {
        $mockJob = $this->createMock(Job::class);
        $job = new SetWikiLogo($wikiKey, $wikiValue, $logoPath);
        $job->setJob($mockJob);
        $mockJob->expects($this->never())
            ->method('fail');
        $job->handle();
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testSetLogoFails($wikiKey, $wikiValue, $logoPath) {
        $storage = Storage::fake('static-assets');
        $this->assertJobFails($wikiKey, $wikiValue, $logoPath);

        if ($wikiKey === 'id') {
            $logoDir = Wiki::getLogosDirectory($wikiValue);
            $storage->assertMissing($logoDir . '/raw.png');
        }
    }

    /**
     * @dataProvider validProvider
     */
    public function testSetLogoSucceeds($wikiKey, $wikiValue, $logoPath) {
        // create user and wiki for this test
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create([$wikiKey => $wikiValue]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        // $wiki = Wiki::firstWhere($wikiKey, $wikiValue);
        $storage = Storage::fake('static-assets');
        $logoDir = Wiki::getLogosDirectory($wiki->id);

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
        $storage->assertExists($logoDir . '/raw.png');
        // check logo resized to 135
        $logo = Image::make($storage->path($logoDir . '/135.png'));
        $this->assertSame(135, $logo->height());
        $this->assertSame(135, $logo->width());
        // check favicon resized to 64
        $logo = Image::make($storage->path($logoDir . '/64.ico'));
        $this->assertSame(64, $logo->height());
        $this->assertSame(64, $logo->width());

        // get the current logo and favicon settings
        $currentLogoSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value);
        $currentFaviconSettingURL = parse_url($wiki->settings()->firstWhere(['name' => WikiSetting::wgFavicon])->value);

        // check the settings have been updated
        $this->assertNotEquals($previousLogoSettingURL, $currentLogoSettingURL);
        $this->assertNotEquals($previousFaviconSettingURL, $currentFaviconSettingURL);

        // check the URL paths are correct
        $this->assertStringEndsWith($logoDir . '/135.png', $currentLogoSettingURL['path']);
        $this->assertStringEndsWith($logoDir . '/64.ico', $currentFaviconSettingURL['path']);
    }

    public static function validProvider() {
        // $wikiKey, $wikiValue, $logoPath
        yield ['id', 42, __DIR__ . '/../data/logo_200x200.png'];
        yield ['domain', 'example.test.dev', __DIR__ . '/../data/logo_200x200.png'];
    }

    public function testSvgLogoHasVectorAndRasterOutputsAndCanBeReplacedWithPng(): void {
        $wiki = Wiki::factory('nodb')->create();
        $storage = Storage::fake('static-assets');
        $directory = Wiki::getLogosDirectory($wiki->id);

        $this->assertJobSucceeds('id', $wiki->id, __DIR__ . '/../data/logo.svg');

        $storage->assertExists([$directory . '/logo.svg', $directory . '/135.png', $directory . '/64.ico']);
        $storage->assertMissing($directory . '/raw.svg');
        $this->assertSame('public', $storage->getVisibility($directory . '/logo.svg'));
        $this->assertStringContainsString('<svg', $storage->get($directory . '/logo.svg'));
        $this->assertStringContainsString('/logo.svg?u=', $wiki->settings()->firstWhere('name', WikiSetting::wwLogoSvg)->value);
        foreach (['135.png' => 135, '64.ico' => 64] as $file => $size) {
            $image = Image::make($storage->path($directory . '/' . $file));
            $this->assertSame($size, $image->width());
            $this->assertSame($size, $image->height());
            $this->assertSame('image/png', $image->mime());
        }

        $this->assertJobSucceeds('id', $wiki->id, __DIR__ . '/../data/logo_200x200.png');
        $this->assertNull($wiki->settings()->firstWhere('name', WikiSetting::wwLogoSvg));
        $this->assertStringContainsString('/135.png?u=', $wiki->settings()->firstWhere('name', WikiSetting::wgLogo)->value);
    }

    public function testSvgStorageFailureDoesNotUpdateSettings(): void {
        $wiki = Wiki::factory('nodb')->create();
        $storage = $this->createMock(FilesystemAdapter::class);
        $storage->expects($this->once())->method('put')->willReturn(false);
        Storage::shouldReceive('disk')->with('static-assets')->andReturn($storage);

        try {
            (new SetWikiLogo('id', $wiki->id, __DIR__ . '/../data/logo.svg'))->handle();
            $this->fail('A failed upload must not report success.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Failed to store the wiki logo.', $e->getMessage());
            $this->assertNull($wiki->settings()->firstWhere('name', WikiSetting::wwLogoSvg));
            $this->assertNull($wiki->settings()->firstWhere('name', WikiSetting::wgLogo));
        }
    }

    public static function invalidProvider() {
        // $wikiKey, $wikiValue, $logoPath
        yield "id doesn't exist" => ['id', 999, __DIR__ . '/../data/logo_200x200.png'];
        yield "logo path doesn't exist" => ['id', 1, '/invalid/logo/path.png'];
        yield "domain doesn't exist" => ['domain', 'non.existant.dev', __DIR__ . '/../data/logo_200x200.png'];
        yield 'invalid key' => ['wikiid', 1, __DIR__ . '/../data/logo_200x200.png'];
    }
}
