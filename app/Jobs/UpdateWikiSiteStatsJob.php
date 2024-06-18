<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSiteStats;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        $update = [];

        $firstEdited = $this->getFirstEditedDate($wiki);
        if ($firstEdited) {
            $update['first_edited'] = $firstEdited;
        }

        $lastEdited = $this->getLastEditedDate($wiki);
        if ($lastEdited) {
            $update['last_edited'] = Carbon::parse($lastEdited);
        }

        $wiki->wikiLifecycleEvents()->updateOrCreate($update);
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
        DB::transaction(function () use ($wiki, $update) {
            $wiki->wikiSiteStats()->lockForUpdate()->updateOrCreate(['wiki_id' => $wiki->id], $update);
        });
    }

    private function getFirstEditedDate (Wiki $wiki): ?\Carbon\CarbonInterface
    {
        $allRevisions = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php',
            [
                'action' => 'query',
                'format' => 'json',
                'list' => 'allrevisions',
                'formatversion' => 2,
                'arvlimit' => 1,
                'arvprop' => 'ids',
                'arvexcludeuser' => 'PlatformReservedUser',
                'arvdir' => 'newer',
            ],
        );
        $firstRevision = data_get($allRevisions->json(), 'query.allrevisions.0.revisions.0.revid');
        if (!$firstRevision) {
            return null;
        }

        $revisionInfo = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php',
            [
                'action' => 'query',
                'format' => 'json',
                'prop' => 'revisions',
                'rvprop' => 'timestamp',
                'formatversion' => 2,
                'revids' => $firstRevision,
            ],
        );
        $result = data_get($revisionInfo->json(), 'query.pages.0.revisions.0.timestamp');
        if (!$result) {
            return null;
        }
        return Carbon::parse($result);
    }

    private function getLastEditedDate (Wiki $wiki): ?\Carbon\CarbonInterface
    {
        $recentChangesInfo = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php',
            [
                'action' => 'query',
                'list' => 'recentchanges',
                'format' => 'json',
            ],
        );
        $result = data_get($recentChangesInfo->json(), 'query.recentchanges.0.timestamp');
        if (!$result) {
            return null;
        }
        return Carbon::parse($result);
    }
}
