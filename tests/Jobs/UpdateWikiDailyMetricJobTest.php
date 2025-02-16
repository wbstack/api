<?php

namespace Tests\Jobs;

use App\Jobs\UpdateWikiDailyMetricJob;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $deletedWiki->delete();

        (new UpdateWikiDailyMetricJob())->handle();

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
