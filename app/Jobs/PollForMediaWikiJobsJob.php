<?php

namespace App\Jobs;

use App\Wiki;

class PollForMediaWikiJobsJob extends Job
{
    public function handle(): void
    {
        $wikis = Wiki::all()->pluck('domain');
        foreach ( $wikis as $wikiDomain ) {
            if ($this->hasPendingJobs( $wikiDomain )) {
                $this->enqueueWiki( $wikiDomain );
            }
        }
    }

    private function hasPendingJobs( string $wikiDomain ): bool
    {
        return true;
    }

    private function enqueueWiki ( string $wikiDomain ): void
    {
        dispatch(new ProcessMediaWikiJobsJob( $wikiDomain ));
    }
}
