<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Http\Client\Pool;
use GuzzleHttp\Promise\Each;

class PollForMediaWikiJobsJob extends Job implements ShouldBeUnique
{
    public $timeout = 3600;
    public function handle (): void
    {
        $wikiDomains = Wiki::all()->pluck('domain');
        $responses = Http::pool(function (Pool $pool) use ($wikiDomains) {
            return [
                Each::ofLimit(
                    (function () use ($pool, $wikiDomains) {
                        foreach ($wikiDomains as $wikiDomain) {
                            yield $pool->async()->withHeaders([
                                'host' => $wikiDomain
                            ])->get(
                                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
                            );
                        }
                    })(),
                    10 // this throttles concurrent execution down to 10
                )
            ];
        });

        foreach ($responses as $index => $response) {
            $wikiDomain = $wikiDomains[$index];

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
