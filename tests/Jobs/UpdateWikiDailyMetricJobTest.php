<?php

namespace Tests\Jobs;

use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\UpdateWikiDailyMetricJob;
use App\Wiki;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

class UpdateWikiDailyMetricJobTest extends TestCase {
    use RefreshDatabase;

    public function testDispatchJob() {
        Queue::fake();

        UpdateWikiDailyMetricJob::dispatch();

        Queue::assertPushed(UpdateWikiDailyMetricJob::class);
    }

    public function testRunJobForAllWikisIncludingDeletedWikis() {
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
        $wikiDbActive->update(['wiki_id' => $activeWiki->id]);

        $wikiDbDeleted = WikiDb::whereDoesntHave('wiki')->first();
        $wikiDbDeleted->update(['wiki_id' => $deletedWiki->id]);

        $deletedWiki->delete();

        (new UpdateWikiDailyMetricJob())->handle();

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $activeWiki->id,
            'date' => Carbon::today()->toDateString(),
            'daily_actions' => null,
            'weekly_actions' => null,
            'monthly_actions' => null,
            'quarterly_actions' => null,
            'item_count' => 0,
            'property_count' => 0,
            'lexeme_count' => 0,
            'entity_schema_count' => 0,
        ]);

        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $deletedWiki->id,
            'date' => Carbon::today()->toDateString(),
            'daily_actions' => null,
            'weekly_actions' => null,
            'monthly_actions' => null,
            'quarterly_actions' => null,
            'item_count' => 0,
            'property_count' => 0,
            'lexeme_count' => 0,
            'entity_schema_count' => 0,
        ]);
    }

    public function testWikiMetricsCollectionStopsEarlyWhenRecordExistsForToday() {
        Log::swap(new LogFake());

        $wiki = Wiki::factory()->create([
            'domain' => 'duplicate.wikibase.cloud',
        ]);

        dispatch(new ProvisionWikiDbJob());

        $wikiDb = WikiDb::whereDoesntHave('wiki')->first();
        $wikiDb->update(['wiki_id' => $wiki->id]);

        $wiki->wikiSiteStats()->create([
            'pages' => 10,
            'users' => 3,
        ]);

        UpdateWikiDailyMetricJob::dispatch();

        $wiki->wikiSiteStats()->first()->update([
            'pages' => 12,
            'users' => 5,
        ]);

        UpdateWikiDailyMetricJob::dispatch();

        $this->assertDatabaseCount('wiki_daily_metrics', 1)
            ->assertDatabaseHas('wiki_daily_metrics', [
                'wiki_id' => $wiki->id,
                'date' => Carbon::today()->toDateString(),
                'pages' => 10,
            ]);

        Log::assertLogged(function (LogEntry $log) use ($wiki) {
            if ($log->level !== 'warning') {
                return false;
            }

            return str_contains($log->message, "Daily metric already exists for Wiki ID {$wiki->id} on " . Carbon::today()->toDateString());
        });
    }
}
