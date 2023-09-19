<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSiteStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateWikiSiteStatsJob extends Job implements ShouldBeUnique
{
    public $timeout = 3600;
    public function handle (): void
    {
        $allWikis = Wiki::all();
        foreach ($allWikis as $wiki) {
            try {
                $this->updateSiteStats($wiki);
                $this->updateLifecycleEvents($wiki);
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$ex->getMessage()
                );
            }
        }
    }

    private function updateLifecycleEvents (Wiki $wiki): void {
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->get(
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&prop=revisions&formatversion=2&rvprop=timestamp&revids=1'
            ),
            $pool->get(
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=recentchanges&format=json'
            ),
        ]);

        $wiki->wikiLifecycleEvents()->updateOrCreate([
            'first_edited' => data_get($responses[0]->json(), 'query.pages.0.revisions.0.timestamp'),
            'last_edited' => data_get($responses[1]->json(), 'query.recentchanges.0.timestamp'),
        ]);
    }

    private function updateSiteStats (Wiki $wiki): void
    {
        $response = Http::withHeaders([
            'host' => $wiki->getAttribute('domain')
        ])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
        );

        if ($response->failed()) {
            throw new \Exception('Request failed with reason '.$response->body());
        }

        $responseBody = $response->json();
        $update = [];
        foreach (WikiSiteStats::FIELDS as $field) {
            $value = data_get($responseBody, 'query.statistics.'.$field, null);
            if ($value !== null) {
                $update[$field] = $value;
            }
        }

        $wiki->wikiSiteStats()->updateOrCreate(['wiki_id' => $wiki->id], $update);
    }
}
