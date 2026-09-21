<?php

namespace Tests\Routes\Wiki;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSiteStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class DeletedWikiMetricsControllerTest extends TestCase {
    use DatabaseTransactions;
    use OptionsRequestAllowed;

    protected string $route = 'deletedWikiMetrics';

    public function testUnauthorizedStatusIfUserNotLoggedIn(): void {
        $this->createAndDeleteTestWiki('one.wikibase.cloud', User::factory()->create());
        $this->createAndDeleteTestWiki('two.wikibase.cloud', User::factory()->create());

        $response = $this->get($this->route);
        $response->assertStatus(401);
    }

    public function testRedirectsIfRegularUserLoggedIn(): void {
        $this->createAndDeleteTestWiki('one.wikibase.cloud', User::factory()->create());
        $this->createAndDeleteTestWiki('two.wikibase.cloud', User::factory()->create());

        $response = $this->actingAs(User::factory()->create(), 'api')->get($this->route);
        $response->assertStatus(302);
    }

    public function testDownloadsCsvIfAdminUser(): void {
        $this->createAndDeleteTestWiki('one.wikibase.cloud', User::factory()->create());
        $this->createAndDeleteTestWiki('two.wikibase.cloud', User::factory()->create());

        $response = $this->actingAs(User::factory()->admin()->create(), 'api')->get($this->route);
        $response->assertStatus(200)
            ->assertDownload(CarbonImmutable::now()->toIso8601String() . '-deleted_wiki_metric.csv');
    }

    public function testOutputHasCorrectContent(): void {
        $user = User::factory()->create();
        $this->createAndDeleteTestWiki('one.wikibase.cloud', $user);
        $this->createAndDeleteTestWiki('two.wikibase.cloud', User::factory()->create(), 'Some Reason');
        $this->createAndDeleteTestWiki('sameuser.wikibase.cloud', $user);

        $response = $this->actingAs(User::factory()->admin()->create(), 'api')->get($this->route);

        $csvArray = array_map('str_getcsv', explode("\n", $response->getContent()));
        $this->assertSame('one.wikibase.cloud', $csvArray[1][0]);
        $this->assertSame('two.wikibase.cloud', $csvArray[2][0]);
        $this->assertSame('Some Reason', $csvArray[2][1]);
    }

    private function createAndDeleteTestWiki(string $domain, User $user, string $wikiDeletionReason = ''): Wiki {
        $current_date = CarbonImmutable::now();

        $wiki = Wiki::factory()->create([
            'domain' => $domain,
            'created_at' => $current_date->subWeeks(1),
        ]);
        WikiManager::factory()->for($user)->for($wiki)->create();
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 77, 'users' => 5]);

        $wiki->save();
        $wiki->update(['wiki_deletion_reason' => $wikiDeletionReason]);
        $wiki->delete();

        return $wiki;
    }
}
