<?php

namespace App\Jobs;

use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
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
        $wikis = Wiki::with('wikiDb')
            ->get();

        foreach ($wikis as $wiki) {
            try {
                $backendUrl = $this->mwHostResolver->getBackendUrlForWiki($wiki);
            } catch (UnknownWikiDomainException | UnknownDBVersionException $e) {
                Log::warning('Skipping wiki ' . $wiki->domain . ' for pending MediaWiki jobs: ' . $e->getMessage());

                continue;
            }

            if ($this->hasPendingJobs($wiki->domain, $backendUrl)) {
                $this->enqueueWiki($wiki->domain);
            }
        }
    }

    private function hasPendingJobs(string $wikiDomain, string $backendUrl): bool {
        $response = Http::withHeaders([
            'host' => $wikiDomain,
        ])->get(
            $backendUrl . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
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
