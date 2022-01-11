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
        $wikis = Wiki::where($this->wikiKey, $this->wikiValue);
        if ($wikis->count() === 0) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiKey}:{$this->wikiValue}"));
            return;
        } elseif ($wikis->count() > 1) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiKey}:{$this->wikiValue}"));
            return;
        }

        $wiki = $wikis->first();

        echo "Wiki ID:{$wiki->id}\n";
        echo "Wiki sitename: {$wiki->sitename}\n";
        echo "Wiki domain: {$wiki->domain}\n";
        echo "logoPath: {$this->logoPath}\n";
        
        return; //safegaurd
    }
}
