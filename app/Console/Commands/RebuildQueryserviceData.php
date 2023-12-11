<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
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

     private $chunkSize;

    public function __construct()
    {
        parent::__construct();
        $this->chunkSize = Config::get('wbstack.qs_rebuild_chunk_size');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $wikiDomain = $this->option('domain');

        $wikis = $wikiDomain
            ? Wiki::where(['domain' => $wikiDomain])->get()
            : Wiki::query()->get();

        foreach ($wikis as $wiki) {
            $entities = $this->getEntitiesForWiki($wiki->domain);
            $sparqlUrl = $this->getSparqlUrl($wiki->wikiQueryserviceNamespace()->namespace);

            $entityChunks = array_chunk($entities, $this->chunkSize);
            foreach ($entityChunks as $entityChunk) {
                dispatch(
                    new TemporaryDummyJob(
                        $wiki->domain,
                        $entityChunk,
                        $sparqlUrl,
                    )
                );
            }
        }

        return 0;
    }

    private function getEntitiesForWiki (Wiki $wiki): array
    {
        $items = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_ITEM);
        $properties = $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_PROPERTY);

        $hasLexemesEnabled = $wiki->settings()->where('name', WikiSetting::wwExtEnableWikibaseLexeme) !== null;
        $lexemes = $hasLexemesEnabled
            ? $this->fetchPagesInNamespace($wiki->domain, self::NAMESPACE_LEXEME)
            : [];

        return array_merge($items, $properties, $lexemes);
    }

    private function getSparqlUrl (string $queryserviceNamespace): string
    {
        return 'http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/'.$queryserviceNamespace.'/sparql';
    }

    private function fetchPagesInNamespace(string $wikiDomain, int $namespace): array
    {
        return [];
    }

    private function stripPrefixes (array $items): array
    {
        return array_map(function (string $item) {
            return preg_replace('/^[a-zA-Z]+:/', '', $item);
        }, $items);
    }
}
