<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollForMediaWikiJobsJob extends Job
{
    public function handle (): void
    {
        $allWikiDomains = Wiki::all()->pluck('domain');
        foreach ($allWikiDomains as $wikiDomain) {
            if ($this->hasPendingJobs($wikiDomain)) {
                $this->enqueueWiki($wikiDomain);
            }
        }
    }

    private function hasPendingJobs (string $wikiDomain): bool
    {
        $response = Http::withHeaders([
            'host' => $wikiDomain
        ])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
        );

        if ($response->failed()) {
            $this->job->markAsFailed();
            Log::error(
                'Failure polling wiki '.$wikiDomain.' for pending MediaWiki jobs: '.$response->clientError()
            );
            return false;
        }

        $pendingJobsCount = data_get($response->json(), 'query.statistics.jobs', 0);
        return $pendingJobsCount > 0;
    }

    private function enqueueWiki (string $wikiDomain): void
    {
        dispatch(new ProcessMediaWikiJobsJob($wikiDomain));
    }
}
