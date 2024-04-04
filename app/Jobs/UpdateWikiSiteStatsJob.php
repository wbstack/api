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
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    protected string $apiUrl;

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

    private function updateEntitiesCount (Wiki $wiki): void
    {
        $item = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_ITEM);
        $property = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_PROPERTY);

        $update = [];

        $items_count = count($item);
        $update['items_count'] = $items_count;

        $properties_count = count($property);
        $update['properties_count'] = $properties_count;

        $wiki->wikiEntitiesCount()->updateOrCreate($update);
    }

    private function fetchPagesInNamespace(string $wikiDomain, int $namespace): array
    {
        $titles = [];
        $cursor = '';
        while (true) {
            $response = Http::withHeaders([
                'host' => $wikiDomain
            ])->get(
                $this->apiUrl,
                [
                    'action' => 'query',
                    'list' => 'allpages',
                    'apnamespace' => $namespace,
                    'apcontinue' => $cursor,
                    'aplimit' => 'max',
                    'format' => 'json',
                ],
            );

            if ($response->failed()) {
                throw new \Exception(
                    'Failed to fetch allpages for wiki '.$wikiDomain
                );
            }

            $jsonResponse = $response->json();
            $error = data_get($jsonResponse, 'error');
            if ($error !== null) {
                throw new \Exception(
                    'Error response fetching allpages for wiki '.$wikiDomain.': '.$error
                );
            }

            $pages = data_get($jsonResponse, 'query.allpages', []);
            $titles = array_merge($titles, array_map(function (array $page) {
                return $page['title'];
            }, $pages));

            $nextCursor = data_get($jsonResponse, 'continue.apcontinue');
            if ($nextCursor === null) {
                break;
            }
            $cursor = $nextCursor;
        }

        return $titles;
    }
}
