<?php

namespace App\Console\Commands;

use App\Jobs\ElasticSearchAliasInit;
use App\Wiki;
use Illuminate\Support\Facades\Config;
use Illuminate\Console\Command;

class EnsureElasticSearchAliases extends Command
{

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
    public function handle()
    {
        $this->info("Dispatching Elasticsearch alias initialization jobs for all wikis...");

        $allUndeletedWikis = Wiki::all();
        foreach ($allUndeletedWikis as $wiki) {
            $this->info("Dispatching job for wiki: {$wiki->domain} (ID: {$wiki->id})");
            $esHosts = Config::get('wbstack.elasticsearch_hosts');
            foreach ($esHosts as $host) {
                ElasticSearchAliasInit::dispatch($wiki->id, $host);
            }
        }

        $this->info("All jobs dispatched successfully.");

        return 0;
    }
}
