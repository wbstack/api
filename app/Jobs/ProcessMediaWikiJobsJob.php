<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;

class ProcessMediaWikiJobsJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable;

    public function __construct ( string $wikiDomain )
    {
        $this->wikiDomain = $wikiDomain;
    }

    public function handle (): void
    {
        return;
    }
}
