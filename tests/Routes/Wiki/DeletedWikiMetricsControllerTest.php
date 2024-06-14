<?php

namespace Tests\Routes\Wiki;
use App\User;
use App\WikiManager;
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

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
        parent::tearDown();
    }

    public function testDownloadCsv()
    {
        $this->createAndDeleteTestWiki('one.wikibase.cloud', 0, '', 1, 2);
        $this->createAndDeleteTestWiki('two.wikibase.cloud', 0, 'Some Reason', 0, 3);
        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload(CarbonImmutable::now()->toIso8601String() . '-deleted_wiki_metric.csv');
    }

    private function createUserAndSetPriviledges($userPriviledge)
    {
        return User::factory()->create(['verified' => true, 'is_admin' => $userPriviledge]);
    }

    private function createAndDeleteTestWiki($name, $user_id, $wikiDeletionReason, $createdWeeksAgo = 1, $wiki_users = 1)
    {
        $current_date = CarbonImmutable::now();

        $wiki = Wiki::factory()->create([
            'domain' => $name, 'sitename' => 'bsite'
        ]);
        WikiManager::factory()->create([
            'wiki_id' => $wiki->id, 'user_id' => $user_id,
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77, 'users' => $wiki_users
        ]);
        $wiki->created_at = $current_date->subWeeks($createdWeeksAgo);

        $wiki->save();
        Wiki::find($wiki->id)->update(['wiki_deletion_reason' => $wikiDeletionReason]);
        Wiki::find($wiki->id)->delete();
    }
}
