<?php

namespace Tests\Routes\Wiki;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\WikiSiteStats;
use App\WikiSetting;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConversionMetricTest extends TestCase
{
    protected string $route = 'wikiConversionData';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
        Carbon::setTestNow(Carbon::parse('first day of October 2023'));
        CarbonImmutable::setTestNow(Carbon::parse('first day of October 2023'));
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function testDownloadCsv()
    {
        $this->createTestWiki('one.wikibase.cloud', 0, 1, 2);
        $this->createTestWiki( 'two.wikibase.cloud', 0, 0, 3 );
        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload(CarbonImmutable::now()->toIso8601String().'-conversion_metric_for_all_wikis.csv');
        $response->assertSee('abandoned');
        $response->assertSee('one.wikibase.cloud');
        $response->assertSee('two.wikibase.cloud');
    }

    private function createTestWiki( $name, $createdWeeksAgo, $firstEditedWeeksAgo, $lastEditedWeeksAgo, $active_users = 0): Wiki {
        $current_date = CarbonImmutable::now();

        $wiki = Wiki::factory()->create([
            'domain' => $name, 'sitename' => 'bsite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77, 'activeusers' => $active_users
        ]);
        $wiki->created_at = $current_date->subWeeks($createdWeeksAgo);
        $events = $wiki->wikiLifecycleEvents();
        $update = [];
        if ($lastEditedWeeksAgo) {
            $update['last_edited'] = $current_date->subWeeks($lastEditedWeeksAgo);
        }
        if ($firstEditedWeeksAgo) {
            $update['first_edited'] = $current_date->subWeeks($firstEditedWeeksAgo);
        }
        $events->updateOrCreate($update);
        $wiki->save();
        return $wiki;
    }

    public function testDownloadJson() {
        $this->createTestWiki('new.but.never.edited.wikibase.cloud', 0, null, null);
        $this->createTestWiki('old.and.never.edited.wikibase.cloud', 53, null, null );
        $this->createTestWiki('old.and.used.only.one.week.wikibase.cloud', 53, 52, 51 );
        $this->createTestWiki('unused.for.a.year.but.now.active.wikibase.cloud', 53, 1, 0, 4 );
        $this->createTestWiki('acvtively.used.for.the.last.year.wikibase.cloud', 53, 53, 0, 5 );
        $this->createTestWiki('creation.time.after.first.edit.wikibase.cloud', 0, 53, 0, 1 );
        $response = $this->getJson($this->route);
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'domain' => 'new.but.never.edited.wikibase.cloud',
                'time_to_engage_days' => null,
                'time_before_wiki_abandoned_days' => null,
                'number_of_active_editors' => 0,
                'first_edited_time' => null,
                'last_edited_time' => null
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'old.and.never.edited.wikibase.cloud',
                'time_to_engage_days' => null,
                'time_before_wiki_abandoned_days' => null,
                'number_of_active_editors' => 0
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'old.and.used.only.one.week.wikibase.cloud',
                'time_to_engage_days' => 7,
                'time_before_wiki_abandoned_days' => 14,
                'number_of_active_editors' => 0,
                'wiki_creation_time' => CarbonImmutable::now()->subWeeks(53),
                'first_edited_time' => CarbonImmutable::now()->subWeeks(52),
                'last_edited_time' => CarbonImmutable::now()->subWeeks(51),
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'unused.for.a.year.but.now.active.wikibase.cloud',
                'time_to_engage_days' => 364,
                'time_before_wiki_abandoned_days' => null,
                'number_of_active_editors' => 4
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'unused.for.a.year.but.now.active.wikibase.cloud',
                'time_to_engage_days' => 0,
                'time_before_wiki_abandoned_days' => null,
                'number_of_active_editors' => 5
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'creation.time.after.first.edit.wikibase.cloud',
                'time_to_engage_days' => -371,
                'time_before_wiki_abandoned_days' => null,
                'number_of_active_editors' => 1
            ]
        );
    }

    public function testFunctionalWithMissingLifecycleEventsandStats() {
        $wiki = Wiki::factory()->create([
            'domain' => 'very.new.wikibase.cloud', 'sitename' => 'bsite'
        ]);

        $response = $this->get($this->route);
        $response->assertStatus(200);
    }
}
