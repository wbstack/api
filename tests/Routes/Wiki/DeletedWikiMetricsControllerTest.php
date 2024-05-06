<?php

namespace Tests\Routes\Wiki;
use App\Http\Controllers\WikiController;
use App\WikiManager;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\WikiSiteStats;
use App\WikiSetting;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeletedWikiMetricsControllerTest extends TestCase
{
    protected string $route = 'deletedWikiMetrics';

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
        $this->createAndDeleteTestWiki('one.wikibase.cloud', 0, '',1, 2);
        $this->createAndDeleteTestWiki( 'two.wikibase.cloud', 0, 'Some Reason', 0, 3 );
        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload(CarbonImmutable::now()->toIso8601String().'-deleted_wiki_metric.csv');
    }

    private function createAndDeleteTestWiki($name, $user_id, $wikiDeletionReason, $createdWeeksAgo = 1, $wiki_users = 1) {
        $current_date = CarbonImmutable::now();

        $wiki = Wiki::factory()->create([
            'domain' => $name, 'sitename' => 'bsite'
        ]);
        WikiManager::factory()->create( [
            'wiki_id' => $wiki->id, 'user_id' => $user_id,
        ] );
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77, 'users' => $wiki_users
        ]);
        $wiki->created_at = $current_date->subWeeks($createdWeeksAgo);

        $wiki->save();
        Wiki::find($wiki)->update(['wiki_deletion_reason' => $wikiDeletionReason]);
        Wiki::find($wiki)->delete();
    }

    public function testDownloadJson() {
        $this->createAndDeleteTestWiki('new.but.never.edited.wikibase.cloud', 0, 'new wiki no edits deleted');
        $this->createAndDeleteTestWiki('old.and.never.edited.wikibase.cloud', 53, 'old wiki no edits deleted');
        $this->createAndDeleteTestWiki('old.and.used.wikibase.cloud', 53, '', 52, 51);
        $this->createAndDeleteTestWiki('no.deletion.reason.wikibase.cloud', 53, '', 2, 4);
        $this->createAndDeleteTestWiki('acvtively.used.for.the.last.year.wikibase.cloud', 53, 'Some weird reason', 53, 5);
        $this->createAndDeleteTestWiki('no.deletion.reason.two.wikibase.cloud', 0,'', 53);
        $response = $this->getJson($this->route);
        $response->assertStatus(200);
        $response->assertJsonFragment(
            [
                'domain' => 'new.but.never.edited.wikibase.cloud',
                'wiki_deletion_reason' => 'new wiki no edits deleted',
                'number_of_users' => 1,
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'old.and.never.edited.wikibase.cloud',
                'wiki_deletion_reason' => 'old wiki no edits deleted',
                'number_of_users' => 1,
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'old.and.used.wikibase.cloud',
                'wiki_deletion_reason' => '',
                'number_of_users' => 51,
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'no.deletion.reason.wikibase.cloud',
                'wiki_deletion_reason' => '',
                'number_of_users' => 4,
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'acvtively.used.for.the.last.year.wikibase.cloud',
                'wiki_deletion_reason' => 'Some weird reason',
                'number_of_users' => 5,
            ]
        );
        $response->assertJsonFragment(
            [
                'domain' => 'no.deletion.reason.two.wikibase.cloud',
                'wiki_deletion_reason' => '',
                'number_of_users' => 1,
            ]
        );
    }
}
