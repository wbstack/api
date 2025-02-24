<?php

namespace Tests\Routes\Wiki\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\Wiki;
use App\WikiSetting;
use App\User;
use App\WikiManager;

class DetailsTest extends TestCase
{
    protected $route = 'wiki/details';

    use OptionsRequestAllowed, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        WikiManager::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        WikiManager::query()->delete();
        parent::tearDown();
    }

    public function testNoCredentials()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        WikiSetting::factory()->create(['name' => 'wwUseQuestyCaptcha', 'value' => 1]);

        $this->postJson($this->route, ['wiki' => $wiki->id])
            ->assertStatus(401);
    }

    public function testSkipsNonPublicSettings()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['domain' => 'other.wikibase.cloud']);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiSetting::factory()->create(['wiki_id' => $wiki->id, 'name' => 'wwUseQuestyCaptcha', 'value' => 1]);
        WikiSetting::factory()->create(['wiki_id' => $wiki->id, 'name' => 'xxxSecret', 'value' => 'foobarbaz']);

        $response = $this->actingAs($user, 'api')->postJson($this->route, ['wiki' => $wiki->id]);

        $response->assertStatus(200);
        $publicSettings = data_get($response->json(), 'data.public_settings', []);
        $this->assertCount(1, $publicSettings);
        $this->assertEquals('wwUseQuestyCaptcha', $publicSettings[0]['name']);
        $this->assertEquals(1, $publicSettings[0]['value']);
    }
}
