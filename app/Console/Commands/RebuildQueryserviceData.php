<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Wiki;
use App\Jobs\TemporaryDummyJob;

class RebuildQueryserviceDataCommand extends Command
{
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

    private function getEntitiesForWiki (string $wikiDomain): array {
        return ['Q1'];
    }

    private function getSparqlUrl (string $queryserviceNamespace): string {
        return 'http://queryservice.default.svc.cluster.local:9999/bigdata/namespace/'.$queryserviceNamespace.'/sparql';
    }
}
