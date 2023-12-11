<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Wiki;
use App\WikiSetting;
use App\Jobs\TemporaryDummyJob;

class RebuildQueryserviceDataCommand extends Command
{
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    private const NAMESPACE_LEXEME = 146;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qs:rebuild {--domain=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the queryservice data for a certain wiki or all wikis';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected int $chunkSize;
    protected string $apiUrl;

    public function __construct()
    {
        parent::__construct();
        $this->chunkSize = Config::get('wbstack.qs_rebuild_chunk_size');
        $this->apiUrl = getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $wikiDomain = $this->option('domain');
        $exitCode = 0;

        $wikis = $wikiDomain
            ? Wiki::where(['domain' => $wikiDomain])->get()
            : Wiki::query()->get();

        foreach ($wikis as $wiki) {
            try {
                $entities = $this->getEntitiesForWiki($wiki);
                $sparqlUrl = $this->getSparqlUrl($wiki);
            } catch (\Exception $ex) {
                Log::error(
                    'Failed to get prerequisites for enqueuing wiki '.$wiki->domain.', will not dispatch jobs.'
                );
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

        $hasLexemesEnabled = $wiki->settings()->where('name', WikiSetting::wwExtEnableWikibaseLexeme) !== null;
        $lexemes = $hasLexemesEnabled
            ? $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_LEXEME)
            : [];

        $merged = array_merge($items, $properties, $lexemes);
        return $this->stripPrefixes($merged);
    }

    private static function getSparqlUrl (Wiki $wiki): string
    {
        $ns = $wiki->wikiQueryserviceNamespace()->namespace;
        return 'http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/'.$ns.'/sparql';
    }

    private function fetchPagesInNamespace(string $wikiDomain, int $namespace): array
    {
        $titles = [];
        $cursor = '';
        while (true)
        {
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
                throw new \Exception('Failed to fetch allpages for wiki '.$wikiDomain);
            }

            $jsonResponse = $response->json();
            $error = data_get($jsonResponse, 'error');
            if ($error !== null) {
                throw new \Exception('Error fetching allpages for wiki '.$wikiDomain.': '.$error);
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
