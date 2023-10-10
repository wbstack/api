<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSiteStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Carbon\Carbon;

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
            $pool->as('revisions')->withHeaders(['host' => $wiki->getAttribute('domain')])->get(
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&prop=revisions&formatversion=2&rvprop=timestamp&revids=1'
            ),
            $pool->as('recentchanges')->withHeaders(['host' => $wiki->getAttribute('domain')])->get(
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&list=recentchanges&format=json'
            ),
        ]);

        $update = [];

        $firstEdited = data_get($responses['revisions']->json(), 'query.pages.0.revisions.0.timestamp');
        if ($firstEdited) {
            $update['first_edited'] = Carbon::parse($firstEdited);
        }

        $lastEdited = data_get($responses['recentchanges']->json(), 'query.recentchanges.0.timestamp');
        if ($lastEdited) {
            $update['last_edited'] = Carbon::parse($lastEdited);
        }

        $wiki->wikiLifecycleEvents()->updateOrCreate($update);
    }

    private function updateEmptyWikibaseNotification (Wiki $wiki): void {
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('revisions')->withHeaders(['host' => $wiki->getAttribute('domain')])->get(
                getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=query&format=json&prop=revisions&formatversion=2&rvprop=timestamp&revids=1'
            ),
        ]);

        $update = [];

        $firstEdited = data_get($responses['revisions']->json(), 'query.pages.0.revisions.0.timestamp');
        if ($firstEdited == null) {
            $update['domain'] = getenv('PLATFORM_MW_BACKEND_HOST');
        }
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
