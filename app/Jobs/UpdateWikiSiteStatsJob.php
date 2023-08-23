<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSiteStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$ex->getMessage()
                );
            }
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
