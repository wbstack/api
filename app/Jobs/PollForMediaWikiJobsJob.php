<?php

namespace App\Jobs;

use App\Services\MediaWikiHostResolver;
use App\Wiki;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollForMediaWikiJobsJob extends Job implements ShouldBeUnique, ShouldQueue {
    private MediaWikiHostResolver $mwHostResolver;

    public $timeout = 1800;

    public function handle(MediaWikiHostResolver $mwHostResolver): void {
        $this->mwHostResolver = $mwHostResolver;
        $allWikiDomains = Wiki::all()->pluck('domain');
        foreach ($allWikiDomains as $wikiDomain) {
            $wiki = Wiki::firstWhere('domain', $wikiDomain);

            // checking for read-only status first
            // to prevent querying the action API
            // from a wiki that is potentially being updated right now
            if (!$wiki->isReadOnly()) {
                if ($this->hasPendingJobs($wikiDomain)) {
                    $this->enqueueWiki($wikiDomain);
                }
            }
        }
    }

    private function hasPendingJobs(string $wikiDomain): bool {
        $response = Http::withHeaders([
            'host' => $wikiDomain,
        ])->get(
            $this->mwHostResolver->getBackendHostForDomain($wikiDomain) . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
        );

        if ($response->failed()) {
            $this->job->markAsFailed();
            Log::error(
                'Failure polling wiki ' . $wikiDomain . ' for pending MediaWiki jobs: ' . $response->clientError()
            );

            return false;
        }

        $pendingJobsCount = data_get($response->json(), 'query.statistics.jobs', 0);

        return $pendingJobsCount > 0;
    }

    private function enqueueWiki(string $wikiDomain): void {
        dispatch(new ProcessMediaWikiJobsJob($wikiDomain));
    }
}
