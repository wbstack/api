<?php

namespace Tests\Metrics;

use App\Metrics\App\WikiMetrics;
use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiMonthlyMetrics;
use App\WikiQuarterlyMetrics;
use App\WikiWeeklyMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiMetricsTest extends TestCase
{
    use RefreshDatabase;
    public function testSuccessfullyAddRecordsDaily()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        (new WikiMetrics())->saveDailySnapshot($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'date' => now()->toDateString()
        ]);
    }
    public function testDoesNotAddDuplicateRecordsWithOnlyDateChangeDaily()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveDailySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeletedDaily()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveDailySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }
    public function testSuccessfullyAddRecordsWeekly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        (new WikiMetrics())->saveWeeklySnapshot($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_weekly_metrics', [
            'date' => now()->toDateString()
        ]);
    }
    public function testDoesNotAddDuplicateRecordsWithOnlyDateChangeWeekly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiWeeklyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveWeeklySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_weekly_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeletedWeekly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiWeeklyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveWeeklySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseHas('wiki_weekly_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }
    public function testSuccessfullyAddRecordsMonthly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        (new WikiMetrics())->saveMonthlySnapshot($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_monthly_metrics', [
            'date' => now()->toDateString()
        ]);
    }
    public function testDoesNotAddDuplicateRecordsWithOnlyDateChangeMonthly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiMonthlyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveMonthlySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_monthly_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeletedMonthly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiMonthlyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveMonthlySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseHas('wiki_monthly_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }
    public function testSuccessfullyAddRecordsQuarterly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);

        (new WikiMetrics())->saveQuarterlySnapshot($wiki);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_quarterly_metrics', [
            'date' => now()->toDateString()
        ]);
    }
    public function testDoesNotAddDuplicateRecordsWithOnlyDateChangeQuarterly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiQuarterlyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveQuarterlySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_quarterly_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeletedQuarterly()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        //Insert an old metric value for a wiki
        WikiQuarterlyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveQuarterlySnapshot($wiki);

        //Assert No new record was created for today
        $this->assertDatabaseHas('wiki_quarterly_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }
}

