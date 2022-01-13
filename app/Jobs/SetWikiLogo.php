<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Intervention\Image\Facades\Image;
use App\WikiSetting;

/**
 * This can be run with for example:
 * php artisan wbs-job:handle SetWikiLogo "domain,wiki.addshore.com,/path/to/logo.png" ,
 * php artisan wbs-job:handle SetWikiLogo "id,1234,/path/to/logo.png" ,
 */
class SetWikiLogo extends Job
{
    private $wikiKey;
    private $wikiValue;
    private $logoPath;

    /**
     * @return void
     */
    public function __construct(string $wikiKey, string $wikiValue, string $logoPath)
    {
        $this->wikiKey = $wikiKey;
        $this->wikiValue = $wikiValue;
        $this->logoPath = $logoPath;
    }

    /**
     * @return void
     */
    public function handle()
    {
        if (!file_exists($this->logoPath)) {
            $this->fail(new \InvalidArgumentException("Logo not found at '{$this->logoPath}'"));
            return;
        }

        $wikis = Wiki::where($this->wikiKey, $this->wikiValue);
        if ($wikis->count() === 0) {
            $this->fail(new \InvalidArgumentException("Wiki not found for key={$this->wikiKey} and value={$this->wikiValue}"));
            return;
        }
        elseif ($wikis->count() > 1) {
            $this->fail(new \InvalidArgumentException("Multiple Wikis matched for key={$this->wikiKey} and value={$this->wikiValue}"));
            return;
        }

        $wiki = $wikis->first();

        // echo "Wiki ID:{$wiki->id}\n";
        // echo "Wiki sitename: {$wiki->sitename}\n";
        // echo "Wiki domain: {$wiki->domain}\n";
        // echo "logoPath: {$this->logoPath}\n";

        // // Get the cloudy disk we use to store logos
        $storage = Storage::disk('gcs-public-static');
        if (!$storage instanceof Cloud) {
            # TODO: Use a more specific exception?
            $this->fail(new \RuntimeException("Invalid storage (not cloud)"));
            return;
        }

        // Get a directory for storing all things relating to this site
        $logosDir = Wiki::getLogosDirectory($wiki->id);
        // Upload the local image to the cloud storage
        $storage->putFileAs($logosDir, new File($this->logoPath), "raw.png");

        // Store a conversion for the actual site logo
        $reducedPath = $logosDir . '/135.png';
        if ($storage->exists($reducedPath)) {
            $storage->delete($reducedPath);
        }
        $storage->writeStream(
            $reducedPath,
            Image::make($storage->path($logosDir . "/raw.png"))->resize(135, 135)->stream()->detach()
        );

        // Store a favicon
        $faviconPath = $logosDir . '/64.ico';
        if ($storage->exists($faviconPath)) {
            $storage->delete($faviconPath);
        }
        $storage->writeStream(
            $faviconPath,
            Image::make($storage->path($logosDir . "/raw.png"))->resize(64, 64)->stream()->detach()
        );

        // Get the urls
        $logoUrl = $storage->url($reducedPath);
        $faviconUrl = $storage->url($faviconPath);
        // Append the time to the url so that client caches will be invalidated
        $logoUrl .= '?u=' . time();
        $faviconUrl .= '?u=' . time();

        // Docs: https://www.mediawiki.org/wiki/Manual:$wgLogo
        // WikiSetting::create(['wiki_id' => $wiki->id, 'name' => WikiSetting::wgLogo, 'value' => $logoUrl]);
        WikiSetting::updateOrCreate(
            ['wiki_id' => $wiki->id, 'name' => WikiSetting::wgLogo],
            ['value' => $logoUrl]
        );

        // WikiSetting::updateOrCreate(
        //     [
        //         'wiki_id' => $wikiId,
        //         'name' => 'wgLogo',
        //     ],
        //     [
        //         'value' => $logoUrl,
        //     ]
        // );

        return; //safegaurd
    }
}