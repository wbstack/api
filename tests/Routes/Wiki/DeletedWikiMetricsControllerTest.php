<?php

namespace Tests\Routes\Wiki;
use App\Http\Controllers\DeletedWikiMetricsController;
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

    public function testUnauthorisedStatusIfUserNotLoggedIn()
    {
        $this->createAndDeleteTestWiki('one.wikibase.cloud', 0, '', 1, 2);
        $this->createAndDeleteTestWiki('two.wikibase.cloud', 10, 'Some Reason', 0, 3);
        $response = $this->get($this->route);
        $response->assertStatus(401);
    }
    public function testRedirectIfUserIsLoggedInAsNotAdmin()
    {
        $user = $this->createUserWithPrivileges(0);
        $this->actingAs($user, 'api')->get('auth/login');
        $this->createAndDeleteTestWiki('one.wikibase.cloud', 0, '', 1, 2);
        $this->createAndDeleteTestWiki('two.wikibase.cloud', 10, 'Some Reason', 0, 3);
        $response = $this->get($this->route);
        $response->assertStatus(302);
    }

    public function testDownloadCsvIfUserIsLoggedInAsAdmin()
    {
        $user = $this->createUserWithPrivileges(1);
        $this->actingAs($user, 'api')->get('auth/login');
        $this->createAndDeleteTestWiki('one.wikibase.cloud', 0, '', 1, 2);
        $this->createAndDeleteTestWiki('two.wikibase.cloud', 10, 'Some Reason', 0, 3);
        $response = $this->get($this->route);
        $response->assertStatus(200)
            ->assertDownload(CarbonImmutable::now()->toIso8601String() . '-deleted_wiki_metric.csv');
    }

    public function testOutputHasCorrectContent()
    {
        $user1 = User::factory()->create(['verified' => true,]);
        $user2 = User::factory()->create(['verified' => true,]);
        $deletedWikis = [
            $this->createAndDeleteTestWiki('one.wikibase.cloud', $user1->id, '', 1, 2),
            $this->createAndDeleteTestWiki('two.wikibase.cloud', $user2->id, 'Some Reason', 0, 3),
            $this->createAndDeleteTestWiki('sameuser.wikibase.cloud', $user1->id, 'Some Reason', 0, 3),
        ];
        $user = $this->createUserWithPrivileges(1);
        $this->actingAs($user, 'api')->get('auth/login');

        $response = $this->get($this->route);

        $rawCsv = $response->getContent();
        $output = array_map('str_getcsv', explode("\n", $rawCsv));
        $this->assertSame('one.wikibase.cloud', $output[1][0]);
        $this->assertSame('two.wikibase.cloud', $output[2][0]);
        $this->assertSame('Some Reason',$output[2][1]);
        $this->assertSame(2, intval($output[1][2]));
    }

    private function createUserWithPrivileges($userPrivilege)
    {
        $password = 'apassword';
        return User::factory()->create([
            'verified' => true,
            'email' => 'atestmail@gmail.com',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'is_admin' => $userPrivilege
        ]);
    }

    private function createAndDeleteTestWiki($domain, $user_id, $wikiDeletionReason, $createdWeeksAgo = 1, $wiki_users = 1)
    {
        $current_date = CarbonImmutable::now();

        $wiki = Wiki::factory()->create([
            'domain' => $domain, 'sitename' => 'bsite'
        ]);
        WikiManager::factory()->create([
            'wiki_id' => $wiki->id, 'user_id' => $user_id,
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77, 'users' => $wiki_users
        ]);
        $wiki->created_at = $current_date->subWeeks($createdWeeksAgo);

        $wiki->save();
        $wiki->update(['wiki_deletion_reason' => $wikiDeletionReason]);
        $wiki->delete();
        return $wiki;
    }
}
