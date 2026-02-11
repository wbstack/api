<?php

namespace App\Console\Commands;

use App\Jobs\ElasticSearchAliasInit;
use App\Wiki;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class EnsureElasticSearchAliases extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wbs-search:ensure-elasticsearch-aliases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->info('Dispatching Elasticsearch alias initialization jobs for all wikis...');

        $allUndeletedWikis = Wiki::all();
        $esHosts = Config::get('wbstack.elasticsearch_hosts');
        foreach ($allUndeletedWikis as $wiki) {
            $this->info("Dispatching job for wiki: {$wiki->domain} (ID: {$wiki->id})");
            foreach ($esHosts as $host) {
                ElasticSearchAliasInit::dispatch($wiki->id, $host);
            }
        }

        // TODO: check that this is actually the case?
        $this->info('All jobs dispatched successfully');

        return 0;
    }
}
