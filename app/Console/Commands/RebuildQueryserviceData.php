<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Wiki;
use App\WikiSetting;
use App\QueryserviceNamespace;
use App\Jobs\TemporaryDummyJob;

class RebuildQueryserviceData extends Command
{
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    private const NAMESPACE_LEXEME = 146;

    protected $signature = 'wbs-qs:rebuild {--domain=*} {--chunkSize=50} {--sparqlUrlFormat=http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/%s/sparql}';

    protected $description = 'Rebuild the queryservice data for a certain wiki or all wikis';

    protected int $chunkSize;
    protected string $apiUrl;
    protected string $sparqlUrlFormat;

    public function handle()
    {
        $this->chunkSize = intval($this->option('chunkSize'));
        $this->sparqlUrlFormat = $this->option('sparqlUrlFormat');
        $this->apiUrl = getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php';

        $wikiDomains = $this->option('domain');
        $exitCode = 0;

        $wikis = count($wikiDomains) !== 0
            ? Wiki::whereIn('domain', $wikiDomains)->get()
            : Wiki::query()->get();

        foreach ($wikis as $wiki) {
            try {
                $entities = $this->getEntitiesForWiki($wiki);
                $sparqlUrl = $this->getSparqlUrl($wiki);
            } catch (\Exception $ex) {
                Log::error(
                    'Failed to get prerequisites for enqueuing wiki '.$wiki->domain.', will not dispatch jobs.'
                );
                Log::error($ex);
                $exitCode = 1;
                break;
            }

            $entityChunks = array_chunk($entities, $this->chunkSize);
            foreach ($entityChunks as $entityChunk) {
                dispatch(
                    new TemporaryDummyJob(
                        $wiki->domain,
                        implode(',', $entityChunk),
                        $sparqlUrl,
                    )
                );
            }
        }

        return $exitCode;
    }

    private function getEntitiesForWiki (Wiki $wiki): array
    {
        $items = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_ITEM);
        $properties = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_PROPERTY);

        $lexemesSetting = WikiSetting::where(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme
            ],
        )->first();
        $hasLexemesEnabled = $lexemesSetting !== null && $lexemesSetting->value === '1';
        $lexemes = $hasLexemesEnabled
            ? $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_LEXEME)
            : [];

        $merged = array_merge($items, $properties, $lexemes);
        return $this->stripPrefixes($merged);
    }

    private function getSparqlUrl (Wiki $wiki): string
    {
        $match = QueryserviceNamespace::where(['wiki_id' => $wiki->id])->first();
        if (!$match) {
            throw new \Exception(
                'Unable to find queryservice namespace record for wiki '.$wiki->domain
            );
        }
        return sprintf($this->sparqlUrlFormat, $match->namespace);
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

    private static function stripPrefixes (array $items): array
    {
        return array_map(function (string $item) {
            return preg_replace('/^[a-zA-Z]+:/', '', $item);
        }, $items);
    }
}
