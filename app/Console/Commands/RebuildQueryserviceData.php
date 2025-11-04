<?php

namespace App\Console\Commands;

use App\Constants\MediawikiNamespace;
use App\Jobs\SpawnQueryserviceUpdaterJob;
use App\QueryserviceNamespace;
use App\Traits;
use App\Wiki;
use App\WikiSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class RebuildQueryserviceData extends Command {
    use Traits\PageFetcher;

    protected $signature = 'wbs-qs:rebuild {--domain=*} {--chunkSize=50} {--queueName=default} {--sparqlUrlFormat=http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/%s/sparql}';

    protected $description = 'Rebuild the queryservice data for a certain wiki or all wikis';

    protected int $chunkSize;

    protected string $sparqlUrlFormat;

    protected string $queueName;

    public function handle() {
        $this->chunkSize = intval($this->option('chunkSize'));
        $this->sparqlUrlFormat = $this->option('sparqlUrlFormat');
        $this->queueName = $this->option('queueName');

        $wikiDomains = $this->option('domain');
        $exitCode = 0;

        $wikis = count($wikiDomains) !== 0
            ? Wiki::whereIn('domain', $wikiDomains)->get()
            : Wiki::query()->get();

        $jobTotal = 0;
        $skippedWikis = 0;
        $processedWikis = 0;
        foreach ($wikis as $wiki) {
            try {
                $entities = $this->getEntitiesForWiki($wiki);
                $sparqlUrl = $this->getSparqlUrl($wiki);
            } catch (\Exception $ex) {
                Log::error(
                    'Failed to get prerequisites for enqueuing wiki ' . $wiki->domain . ', will not dispatch jobs.',
                    [$ex],
                );
                $exitCode = 1;
                $skippedWikis += 1;
                break;
            }

            $entityChunks = array_chunk($entities, $this->chunkSize);
            foreach ($entityChunks as $entityChunk) {
                Queue::pushOn($this->queueName, new SpawnQueryserviceUpdaterJob(
                    $wiki->domain,
                    implode(',', $entityChunk),
                    $sparqlUrl,
                ));
            }
            $jobTotal += count($entityChunks);
            $processedWikis += 1;
            Log::info('Dispatched ' . count($entityChunks) . ' job(s) for wiki ' . $wiki->domain . '.');
        }

        Log::info(
            'Done. Jobs dispatched: ' . $jobTotal . ' Wikis processed: ' . $processedWikis . ' Wikis skipped: ' . $skippedWikis
        );

        return $exitCode;
    }

    private function getEntitiesForWiki(Wiki $wiki): array {
        $items = $this->fetchPagesInNamespace($wiki->domain, MediawikiNamespace::item);
        $properties = $this->fetchPagesInNamespace($wiki->domain, MediawikiNamespace::property);

        $lexemesSetting = WikiSetting::where(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
            ],
        )->first();
        $hasLexemesEnabled = $lexemesSetting !== null && $lexemesSetting->value === '1';
        $lexemes = $hasLexemesEnabled
            ? $this->fetchPagesInNamespace($wiki->domain, MediawikiNamespace::lexeme)
            : [];

        $merged = array_merge($items, $properties, $lexemes);
        $this->stripPrefixes($merged);

        return $merged;
    }

    private function getSparqlUrl(Wiki $wiki): string {
        $match = QueryserviceNamespace::where(['wiki_id' => $wiki->id])->first();
        if (!$match) {
            throw new \Exception(
                'Unable to find queryservice namespace record for wiki ' . $wiki->domain
            );
        }

        return sprintf($this->sparqlUrlFormat, $match->namespace);
    }

    private static function stripPrefixes(array &$items): void {
        foreach ($items as &$item) {
            $e = explode(':', $item);
            $item = end($e);
        }
    }
}
