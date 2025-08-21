<?php

namespace Tests\Routes\Wiki\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\Wiki;
use App\WikiSetting;
use App\User;
use App\WikiManager;
use App\WikiProfile;

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

    public function testReturnsCorrectWikiNotFirstWiki(): void
    {
        $firstWiki = Wiki::factory()->create();
        $userWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $this->assertEquals($firstWiki->id, Wiki::first()->id);
        $this->actingAs($user, 'api')
            ->postJson($this->route, ['wiki' => $userWiki->id])
            ->assertJsonPath('data.id', $userWiki->id)
            ->assertStatus(200);
    }

    public function testFailOnWrongWikiManager(): void
    {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $this->actingAs($user, 'api')
            ->postJson($this->route, ['wiki' => $otherWiki->id])
            ->assertStatus(403);
    }

    public function testWikiProfile()
    {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $versionA = WikiProfile::create([
            'wiki_id' => $wiki->id,
            'audience' => 'wide',
            'temporality' => 'temporary',
            'purpose' => 'data_hub'
        ])->refresh()->toArray();

        $response = $this->actingAs($user, 'api')
        ->postJson($this->route, ['wiki' => $wiki->id])
        ->assertStatus(200);

        $profile = data_get($response->json(), 'data.wiki_latest_profile', []);
        $this->assertNotEmpty($profile);
        $this->assertEquals($versionA, $profile);

        $versionB = WikiProfile::create([
            'wiki_id' => $wiki->id,
            'audience' => 'wide',
            'temporality' => 'permanent',
            'purpose' => 'data_hub'
        ])->refresh()->toArray();

        $response = $this->actingAs($user, 'api')
        ->postJson($this->route, ['wiki' => $wiki->id])
        ->assertStatus(200);

        $profile = data_get($response->json(), 'data.wiki_latest_profile', []);
        $this->assertNotEmpty($profile);
        $this->assertEquals($versionB, $profile);
    }
}
