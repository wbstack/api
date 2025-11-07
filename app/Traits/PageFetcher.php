<?php

namespace App\Traits;

use App\Constants\MediawikiNamespace;
use App\Services\MediaWikiHostResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;

trait PageFetcher {
    // this function is used to fetch pages on namespace
    public function fetchPagesInNamespace(string $wikiDomain, MediawikiNamespace $namespace): array {
        $mwHostResolver = App::make(MediaWikiHostResolver::class);
        $apiUrl = $mwHostResolver->getBackendHostForDomain($wikiDomain) . '/w/api.php';

        $titles = [];
        $cursor = '';
        while (true) {
            $response = Http::withHeaders([
                'host' => $wikiDomain,
            ])->get(
                $apiUrl,
                [
                    'action' => 'query',
                    'list' => 'allpages',
                    'apnamespace' => $namespace->value,
                    'apcontinue' => $cursor,
                    'aplimit' => 'max',
                    'format' => 'json',
                ],
            );

            if ($response->failed()) {
                throw new \Exception(
                    'Failed to fetch allpages for wiki ' . $wikiDomain
                );
            }

            $jsonResponse = $response->json();
            $error = data_get($jsonResponse, 'error');
            if ($error !== null) {
                throw new \Exception(
                    'Error response fetching allpages for wiki ' . $wikiDomain . ': ' . $error
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
