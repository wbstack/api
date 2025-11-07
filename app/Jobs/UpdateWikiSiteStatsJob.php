<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSiteStats;
use App\Services\MediaWikiHostResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateWikiSiteStatsJob extends Job implements ShouldBeUnique {
    use Dispatchable;

    public $timeout = 3600;

    private MediaWikiHostResolver $mwHostResolver;

    public function handle(MediaWikiHostResolver $mwHostResolver): void {
        $this->mwHostResolver = $mwHostResolver;

        $allWikis = Wiki::all();
        foreach ($allWikis as $wiki) {
            try {
                $this->updateSiteStats($wiki);
                $this->updateLifecycleEvents($wiki);
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki ' . $wiki->getAttribute('domain') . ' for sitestats: ' . $ex->getMessage()
                );
            }
        }
    }

    private function updateLifecycleEvents(Wiki $wiki): void {
        $update = [];

        $firstEdited = $this->getFirstEditedDate($wiki);
        if ($firstEdited) {
            $update['first_edited'] = $firstEdited;
        }

        $lastEdited = $this->getLastEditedDate($wiki);
        if ($lastEdited) {
            $update['last_edited'] = Carbon::parse($lastEdited);
        }

        DB::transaction(function () use ($wiki, $update) {
            $wiki->wikiLifecycleEvents()->lockForUpdate()->updateOrCreate(['wiki_id' => $wiki->id], $update);
        });
    }

    private function updateSiteStats(Wiki $wiki): void {
        $response = Http::withHeaders([
            'host' => $wiki->getAttribute('domain'),
        ])->get(
            $this->mwHostResolver->getBackendHostForDomain($wiki->domain) . '/w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json'
        );

        if ($response->failed()) {
            throw new \Exception('Request failed with reason ' . $response->body());
        }

        $responseBody = $response->json();
        $update = [];
        foreach (WikiSiteStats::FIELDS as $field) {
            $value = data_get($responseBody, 'query.statistics.' . $field, null);
            if ($value !== null) {
                $update[$field] = $value;
            }
        }
        DB::transaction(function () use ($wiki, $update) {
            $wiki->wikiSiteStats()->lockForUpdate()->updateOrCreate(['wiki_id' => $wiki->id], $update);
        });
    }

    private function getFirstEditedDate(Wiki $wiki): ?CarbonInterface {
        $allRevisions = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            $this->mwHostResolver->getBackendHostForDomain($wiki->domain) . '/w/api.php',
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
            $this->mwHostResolver->getBackendHostForDomain($wiki->domain) . '/w/api.php',
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

    private function getLastEditedDate(Wiki $wiki): ?CarbonInterface {
        $allRevisions = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            $this->mwHostResolver->getBackendHostForDomain($wiki->domain) . '/w/api.php',
            [
                'action' => 'query',
                'format' => 'json',
                'list' => 'allrevisions',
                'formatversion' => 2,
                'arvlimit' => 1,
                'arvprop' => 'ids',
                'arvexcludeuser' => 'PlatformReservedUser',
                'arvdir' => 'older',
            ],
        );
        $lastRevision = data_get($allRevisions->json(), 'query.allrevisions.0.revisions.0.revid');
        if (!$lastRevision) {
            return null;
        }

        $revisionInfo = Http::withHeaders(['host' => $wiki->getAttribute('domain')])->get(
            $this->mwHostResolver->getBackendHostForDomain($wiki->domain) . '/w/api.php',
            [
                'action' => 'query',
                'format' => 'json',
                'prop' => 'revisions',
                'rvprop' => 'timestamp',
                'formatversion' => 2,
                'revids' => $lastRevision,
            ],
        );
        $result = data_get($revisionInfo->json(), 'query.pages.0.revisions.0.timestamp');
        if (!$result) {
            return null;
        }

        return Carbon::parse($result);
    }
}
