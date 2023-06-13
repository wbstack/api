<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\Pool;

class PollForMediaWikiJobsJob extends Job implements ShouldBeUnique
{
    public $timeout = 3600;
    public function handle (): void
    {
        $allWikiDomains = Wiki::all()->pluck('domain');
        $responses = Http::pool(function (Pool $pool) use ($allWikiDomains) {
            foreach ($allWikiDomains as $wikiDomain) {
                $pool->withHeaders([
                    'host' => $wikiDomain
                ])->get(
                    getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
                );
            }
        });

        foreach ($responses as $index => $response) {
            $wikiDomain = $allWikiDomains[$index];

            if ($response->failed()) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wikiDomain.' for pending MediaWiki jobs: '.$response->clientError()
                );
                continue;
            }

            if ($this->hasPendingJobs($response->json())) {
                $this->enqueueWiki($wikiDomain);
            }
        }
    }

    private function hasPendingJobs (array $siteinfo): bool
    {
        $pendingJobsCount = data_get($siteinfo, 'query.statistics.jobs', 0);
        return $pendingJobsCount > 0;
    }

    private function enqueueWiki (string $wikiDomain): void
    {
        dispatch(new ProcessMediaWikiJobsJob($wikiDomain));
    }
}
