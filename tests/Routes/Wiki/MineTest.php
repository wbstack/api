<?php

namespace Tests\Routes\Wiki\Managers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSuspension;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class MineTest extends TestCase {
    protected $route = 'wiki/mine';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    public function testMineDefault() {
        Config::set('wbstack.wiki_max_per_user', false);

        $user = User::factory()->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('POST', $this->route, [])
            ->assertStatus(200)
            ->assertJsonFragment(['wikis' => [], 'count' => 0, 'limit' => false]);
    }

    public function testMineWithWikis() {
        Config::set('wbstack.wiki_max_per_user', 1);

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $content = $this->actingAs($user, 'api')
            ->json('POST', $this->route, [])
            ->assertStatus(200)
            ->assertJson(['wikis' => [], 'count' => 1, 'limit' => 1])->getContent();

        $this->assertEquals($wiki->id, json_decode($content)->wikis[0]->id);
    }

    public function testMineIncludesPendingReviewSubmissions() {
        $wikiManagerUser = User::factory()->create(['verified' => true]);

        $wikiWithNoSubmissions = Wiki::factory()
            ->has(WikiManager::factory())
            ->recycle($wikiManagerUser)
            ->create();

        $wikiWithPendingSubmission = Wiki::factory()
            ->has(WikiManager::factory())
            ->recycle($wikiManagerUser)
            ->create();
        ReviewSubmission::factory()
            ->for($wikiWithPendingSubmission)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->recycle($wikiManagerUser)
            ->create();

        $wikiWithApprovedSubmission = Wiki::factory()
            ->has(WikiManager::factory())
            ->recycle($wikiManagerUser)
            ->create();
        ReviewSubmission::factory()
            ->for($wikiWithApprovedSubmission)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $this->actingAs($wikiManagerUser, 'api')
            ->postJson($this->route)
            ->assertStatus(200)
            ->assertJson(['wikis' => [
                ['id' => $wikiWithNoSubmissions->id, 'has_pending_review' => false],
                ['id' => $wikiWithPendingSubmission->id, 'has_pending_review' => true],
                ['id' => $wikiWithApprovedSubmission->id, 'has_pending_review' => false],
            ]]);
    }

    public function testMineIncludesScheduledSuspensions() {
        $wikiManagerUser = User::factory()->create(['verified' => true]);

        $wikiWithNoSuspensions = Wiki::factory()
            ->has(WikiManager::factory())
            ->recycle($wikiManagerUser)
            ->create();

        $wikiWithSuspension = Wiki::factory()
            ->has(WikiManager::factory())
            ->recycle($wikiManagerUser)
            ->create();
        $suspension = WikiSuspension::factory()
            ->for($wikiWithSuspension)
            ->create();

        $this->actingAs($wikiManagerUser, 'api')
            ->json('POST', $this->route, [])
            ->assertStatus(200)
            ->assertJson(['wikis' => [
                ['id' => $wikiWithNoSuspensions->id, 'latest_suspension' => null],
                ['id' => $wikiWithSuspension->id, 'latest_suspension' => [
                    'id' => $suspension->id,
                    'active' => true,
                ]],
            ]]);
    }
}
