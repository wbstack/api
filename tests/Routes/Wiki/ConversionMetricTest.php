<?php

namespace Tests\Routes\Wiki;

use App\WikiLifecycleEvents;
use Carbon\Carbon;
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
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
        parent::tearDown();
    }

    public function testEmpty()
    {
        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function testGetJsonAll()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'bsite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77
        ]);

        $current_date = Carbon::now();
        WikiLifecycleEvents::factory()->create([
            'first_edited' => $current_date, 'last_edited' => $current_date, 'wiki_id' => $wiki->id,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'asite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 66
        ]);
        $current_date = Carbon::now();
        WikiLifecycleEvents::factory()->create([
            'first_edited' => $current_date, 'last_edited' => $current_date, 'wiki_id' => $wiki->id,
        ]);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 66)
            ->assertJsonPath('data.0.wiki_lifecycle_events.first_edited', $current_date)
            ->assertJsonPath('data.0.wiki_lifecycle_events.last_edited', $current_date)
            ->assertJsonPath('data.1.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 77)
            ->assertJsonPath('data.1.wiki_lifecycle_events.first_edited', $current_date)
            ->assertJsonPath('data.1.wiki_lifecycle_events.last_edited', $current_date);
    }

    public function testDownloadCsvFile()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'bsite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77
        ]);

        WikiLifecycleEvents::factory()->create([
            'first_edited' => Carbon::now(), 'last_edited' => Carbon::now(), 'wiki_id' => $wiki->id,
        ]);

        $response = $this->get($this->route);

        $response->assertStatus(200)
            ->assertDownload('conversion_metric_for_all_wikis.txt');
    }
}
