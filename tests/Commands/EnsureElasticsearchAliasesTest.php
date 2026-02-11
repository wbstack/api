<?php

namespace Tests\Commands;

use App\Jobs\ElasticSearchAliasInit;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EnsureElasticsearchAliasesTest extends TestCase {
    use DatabaseTransactions;

    public function testDispatchesElasticsearchAliasInitForAllWikis() {
        // setup
        Bus::fake();

        // create some wikis
        $wiki1 = Wiki::factory()->create(['domain' => 'wiki1.wikibase.cloud']);
        $wiki2 = Wiki::factory()->create(['domain' => 'wiki2.wikibase.cloud']);

        // configure elasticsearch hosts
        $esHost1 = 'es1.example.com:9200';
        $esHost2 = 'es2.example.com:9200';
        Config::set('wbstack.elasticsearch_hosts', [$esHost1, $esHost2]);

        // run the command
        $this->artisan('wbs-search:ensure-elasticsearch-aliases')->assertExitCode(0);

        // build a list of expected jobs based on the wikis and elasticsearch hosts configured
        $allUndeletedWikis = Wiki::all();
        $expectedJobs = [];
        foreach ($allUndeletedWikis as $wiki) {
            // null is just a random value; it's easier to unset the host if it's an array key
            $expectedJobs[$wiki->id] = [$esHost1 => null, $esHost2 => null];
        }

        // each wiki should have 2 jobs dispatched (one for each elasticsearch host)
        Bus::assertDispatchedTimes(ElasticSearchAliasInit::class, Wiki::all()->count() * 2);
        Bus::assertDispatched(ElasticSearchAliasInit::class, function ($job) use (&$expectedJobs) {
            $this->assertArrayHasKey($job->wikiId, $expectedJobs);
            $this->assertArrayHasKey($job->esHost, $expectedJobs[$job->wikiId]);
            // delete the host from the expected jobs to ensure we don't have duplicate jobs for the same wiki and host
            unset($expectedJobs[$job->wikiId][$job->esHost]);
            if (empty($expectedJobs[$job->wikiId])) {
                unset($expectedJobs[$job->wikiId]);
            }

            return true;
        });
        // assert that there are dispatched jobs for each wiki and for each ES host
        $this->assertEmpty($expectedJobs, 'Not all expected jobs were dispatched');
    }
}
