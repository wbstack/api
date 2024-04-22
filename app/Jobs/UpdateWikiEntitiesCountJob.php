<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateWikiEntitiesCountJob extends Job implements ShouldBeUnique
{
    private const NAMESPACE_ITEM = 122;
    private const NAMESPACE_PROPERTY = 120;
    protected string $apiUrl;

    public function handle (): void
    {
        $this->apiUrl = getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php';
        $allWiki = Wiki::all();
        foreach ($allWiki as $wiki) {
            try{
                $this->updateEntitiesCount($wiki);
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$ex->getMessage()
                );
            }
        }
    }

    private function updateEntitiesCount (Wiki $wiki): void
    {
        $items_count = count($this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_ITEM));
        $properties_count = count($this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_PROPERTY));

        $update = [];

        $update['items_count'] = $items_count;
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
