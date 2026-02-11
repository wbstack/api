<?php

namespace Tests\Commands;

use App\Constants\MediawikiNamespace;
use App\Jobs\ElasticSearchAliasInit;
use App\QueryserviceNamespace;
use App\Services\MediaWikiHostResolver;
use App\Wiki;
use App\WikiSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnsureElasticsearchAliasesTest extends TestCase {
    use DatabaseTransactions;

    public function testDispatchesElasticsearchAliasInitForAllWikis() {
        // setup
        Bus::fake();

        // create some wikis
        $wiki = Wiki::factory()->create(['domain' => 'rebuild.wikibase.cloud']);

        // configure elasticsearch hosts
        Config::set('wbstack.elasticsearch_hosts', ['es1.example.com:9200', 'es2.example.com:9200']);

        $this->artisan('wbs-search:ensure-elasticsearch-aliases')->assertExitCode(0);

        // assert that there are dispatched jobs for each wiki and for each ES host.

        Bus::assertDispatchedTimes(ElasticSearchAliasInit::class, 2);
    }
}
