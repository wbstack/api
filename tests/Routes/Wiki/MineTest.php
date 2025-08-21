<?php

namespace Tests\Routes\Wiki\Managers;

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
}
