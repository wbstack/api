<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSetting;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\QueryException;
use Illuminate\Http\File;
use Intervention\Image\Facades\Image;
use Storage;

/**
 * This can be run with the artisan job command, for example:
 * php artisan job:dispatchNow SetWikiLogo domain wiki.addshore.com /path/to/logo.png
 * php artisan job:dispatchNow SetWikiLogo id 1234 /path/to/logo.png
 * 
 * NOTE: This job needs to be run as the correct user if run via artisan (instead of via the UI)
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

        try {
            $wikis = Wiki::where($this->wikiKey, $this->wikiValue);
            if ($wikis->count() === 0) {
                $this->fail(new \InvalidArgumentException("Wiki not found for key={$this->wikiKey} and value={$this->wikiValue}"));
                return;
            }
            elseif ($wikis->count() > 1) {
                $this->fail(new \InvalidArgumentException("Multiple Wikis matched for key={$this->wikiKey} and value={$this->wikiValue}"));
                return;
            }
        } catch(QueryException $e) {
            $this->fail(new \InvalidArgumentException("Invalid key ({$this->wikiKey}) or value ({$this->wikiValue})"));
            return;
        }

        $wiki = $wikis->first();

        // Get the cloud disk we use to store logos
        $storage = Storage::disk('gcs-public-static');
        if (!$storage instanceof Cloud) {
            # TODO: Use a more specific exception?
            $this->fail(new \RuntimeException("Invalid storage (not cloud)"));
            return;
        }

        // Get the directory for storing this site's logos
        $logosDir = Wiki::getLogosDirectory($wiki->id);
        $rawPath = $logosDir . "/raw.png";

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

        // Store a conversion for the favicon
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
        WikiSetting::updateOrCreate(
            ['wiki_id' => $wiki->id, 'name' => WikiSetting::wgLogo],
            ['value' => $logoUrl]
        );

        // Docs: https://www.mediawiki.org/wiki/Manual:$wgLogo
        WikiSetting::updateOrCreate(
            ['wiki_id' => $wiki->id, 'name' => WikiSetting::wgFavicon],
            ['value' => $faviconUrl]
        );

        return; //safeguard
    }
}
