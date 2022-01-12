<?php

namespace App\Jobs;

use App\Wiki;

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
            $this->fail(new \RuntimeException("Wiki not found for key={$this->wikiKey} and value={$this->wikiValue}"));
            return;
        }
        elseif ($wikis->count() > 1) {
            $this->fail(new \RuntimeException("Multiple Wikis matched for key={$this->wikiKey} and value={$this->wikiValue}"));
            return;
        }

        $wiki = $wikis->first();

        echo "Wiki ID:{$wiki->id}\n";
        echo "Wiki sitename: {$wiki->sitename}\n";
        echo "Wiki domain: {$wiki->domain}\n";
        echo "logoPath: {$this->logoPath}\n";

        // // Get the cloudy disk we use to store logos
        // $storage = Storage::disk('gcs-public-static');
        // if (! $storage instanceof Cloud) {
        //     return response()->json('Invalid storage (not cloud)', 500);
        // }

        // // Get a directory for storing all things relating to this site
        // $logosDir = Wiki::getLogosDirectory($wiki->id);
        // // Upload the local image to the cloud storage
        // $storage::putFile($logosDir, new File($this->logoPath))

        return; //safegaurd
    }
}
