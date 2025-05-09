<?php

namespace Tests\Jobs;

use App\Jobs\UpdateWikiDailyMetricJob;
use App\Wiki;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Jobs\ProvisionWikiDbJob;

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
        $job = new ProvisionWikiDbJob();
        $job2 = new ProvisionWikiDbJob();
        $job->handle($manager);
        $job2->handle($manager);

        $wikiDbActive = WikiDb::whereDoesntHave('wiki')->first();
        $wikiDbActive->update( ['wiki_id' => $activeWiki->id] );

        $wikiDbDeleted = WikiDb::whereDoesntHave('wiki')->first();
        $wikiDbDeleted->update( ['wiki_id' => $deletedWiki->id] );

        $deletedWiki->delete();



        (new UpdateWikiDailyMetricJob())->handle();

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $activeWiki->id,
            'date' => Carbon::today()->toDateString(),
            'daily_actions' => null,
            'weekly_actions' => null,
            'monthly_actions' => null,
            'quarterly_actions' => null,
        ]);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $deletedWiki->id,
            'date' => Carbon::today()->toDateString(),
            'daily_actions' => null,
            'weekly_actions' => null,
            'monthly_actions' => null,
            'quarterly_actions' => null,
        ]);
    }

}
