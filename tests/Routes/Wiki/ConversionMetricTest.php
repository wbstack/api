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

    public function testEmpty()
    {
        $this->instance(
            ResponseFactory::class, Mockery::mock(ResponseFactory::class, function ($mock) {
            $mock->shouldReceive('download')
                ->with([["domain_name,time_to_engage_days,time_since_wiki_abandoned_days,number_of_active_editors"]],
                'conversion_metric_for_all_wikis.csv')
                ->once()
                ->andReturn(['header' => 'data']);
        }));
        $response = $this->get($this->route);
        $response->assertStatus(200);
    }

    public function testDownloadCsv()
    {
    /*    $this->instance(
            ResponseFactory::class, Mockery::mock(ResponseFactory::class, function ($mock) {
            $mock->shouldReceive('download')
                ->with()
                ->once()
                ->andReturn(['header' => 'data']);
        }));*/
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

        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload('conversion_metric_for_all_wikis.csv');;
    }
}
