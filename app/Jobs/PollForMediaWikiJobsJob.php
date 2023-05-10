<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Support\Facades\Http;

class PollForMediaWikiJobsJob extends Job
{
    public function handle(): void
    {
        $wikis = Wiki::all()->pluck('domain');
        foreach ($wikis as $wikiDomain) {
            if ($this->hasPendingJobs($wikiDomain)) {
                $this->enqueueWiki($wikiDomain);
            }
        }
    }

    private function hasPendingJobs(string $wikiDomain): bool
    {
        $response = Http::withHeaders([
            'host' => $wikiDomain
        ])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
        );

        if ($response->failed()) {
            $this->fail(
                new \RuntimeException(
                    'Failure polling wiki '.$wikiDomain.' for pending MediaWiki jobs: '.$response->clientError()
                )
            );

            return false;
        }

        $pendingJobs = data_get($response->json(), 'query.statistics.jobs', 0);
        return $pendingJobs > 0;
    }

    private function enqueueWiki (string $wikiDomain): void
    {
        dispatch(new ProcessMediaWikiJobsJob($wikiDomain));
    }
}
