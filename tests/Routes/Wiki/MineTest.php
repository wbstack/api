<?php

namespace Tests\Routes\Wiki\Managers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\User;
use App\Wiki;
use App\WikiManager;
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
        $user = User::factory()->create(['verified' => true]);

        $wikiNoSubmission = Wiki::factory()->create();

        $wikiSubmission = Wiki::factory()->create();

        WikiManager::factory()
            ->for($user)
            ->for($wikiNoSubmission)
            ->create();

        WikiManager::factory()
            ->for($user)
            ->for($wikiSubmission)
            ->create();

        $reviewSubmission = ReviewSubmission::factory()
            ->for($wikiSubmission)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->create();

        $this->actingAs($user, 'api')
            ->json('POST', $this->route, [])
            ->assertStatus(200)
            ->assertJson(['wikis' => [
                ['id' => $wikiNoSubmission->id, 'hasPendingReview' => false],
                ['id' => $wikiSubmission->id, 'hasPendingReview' => true],
            ]]);

    }
}
