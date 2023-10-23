<?php

namespace Tests\Routes\Wiki;

use App\WikiLifecycleEvents;
use Carbon\Carbon;
use Illuminate\Routing\ResponseFactory;
use Mockery;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\WikiSiteStats;
use App\WikiSetting;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use function PHPUnit\Framework\assertSame;

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

    public function testDownloadCsv()
    {
        $wikiOne = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'bsite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wikiOne->id, 'pages' => 77
        ]);

        $current_date = Carbon::now();
        $eventsOne = $wikiOne->wikiLifecycleEvents();
        $eventsOne->updateOrCreate(['last_edited' => $current_date->subWeeks(1), 'first_edited' => $current_date->subWeeks(2) ]);

        $wikiTwo = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'bsite'
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wikiOne->id, 'pages' => 234
        ]);

        $eventsTwo = $wikiTwo->wikiLifecycleEvents();
        $eventsTwo->updateOrCreate(['last_edited' => $current_date, 'first_edited' => $current_date->subDays(3) ]);

        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload('conversion_metric_for_all_wikis.csv');
        $response->assertSee('abandoned');
        $response->assertSee('one.wikibase.cloud');
        $response->assertSee('two.wikibase.cloud');
    }
}
