<?php

namespace Tests\Jobs;

use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\UpdateWikiDailyMetricJob;
use App\Wiki;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateWikiDailyMetricJobTest extends TestCase
{

    use RefreshDatabase;


    public function testDispatchJob()
    {
        Queue::fake();

        UpdateWikiDailyMetricJob::dispatch();

        Queue::assertPushed(UpdateWikiDailyMetricJob::class);
    }


    public function testRunJobForAllWikisIncludingDeletedWikis()
    {
        $activeWiki = Wiki::factory()->create([
            'domain' => 'example.wikibase.cloud',
        ]);
        $deletedWiki = Wiki::factory()->create([
            'domain' => 'deletedwiki.wikibase.cloud',
        ]);
        $manager = $this->app->make('db');
        $job = new ProvisionWikiDbJob(null, 'deletedwikidb', 1);
        $job2 = new ProvisionWikiDbJob(null, 'activewikidb', 1);
        $job->handle($manager);
        $job2->handle($manager);
        DB::table('wiki_dbs')->where(["wiki_id" => null])->limit(1)->value('name');
        DB::table('wiki_dbs')->where(["wiki_id" => null])->update(['wiki_id' => $activeWiki->id]);
        DB::table('wiki_dbs')->where(["wiki_id" => null])->limit(1)->value('name');
        DB::table('wiki_dbs')->where(["wiki_id" => null])->update(['wiki_id' => $deletedWiki->id]);

        $deletedWiki->delete();

        (new UpdateWikiDailyMetricJob())->handle($manager);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $activeWiki->id,
            'date' => Carbon::today()->toDateString()
        ]);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $deletedWiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

}
