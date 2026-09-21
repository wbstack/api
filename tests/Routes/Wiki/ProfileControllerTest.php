<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiProfile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfileControllerTest extends TestCase {
    use DatabaseTransactions;

    protected $route = 'wiki/profile';

    public function testFailOnMissingWiki(): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $wiki->delete();

        $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $wiki->id,
                    'profile' => json_encode([
                        'audience' => 'wide',
                        'temporality' => 'permanent',
                        'purpose' => 'data_hub',
                    ]),
                ]
            )
            ->assertStatus(404);
    }

    public function testFailOnWrongWikiManager(): void {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $otherWiki->id,
                    'profile' => json_encode([
                        'audience' => 'wide',
                        'temporality' => 'permanent',
                        'purpose' => 'data_hub',
                    ]),
                ]
            )
            ->assertStatus(403);
    }

    public function testFailOnEmptyProfile(): void {
        $userWiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $userWiki->id,
                    'profile' => '{}',
                ]
            )
            ->assertStatus(422);
    }

    public function testFailOnInvalidProfile(): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $wiki->id,
                    'profile' => json_encode([
                        'audience' => 'invalid option',
                        'temporality' => 'permanent',
                        'purpose' => 'data_hub',
                    ]),
                ]
            )
            ->assertStatus(422)
            ->assertJson([
                'message' => 'The selected audience is invalid.',
                'errors' => [
                    'audience' => [
                        'The selected audience is invalid.',
                    ],
                ],
            ]);
    }

    public function testKeepAllVersions(): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $versionA = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $wiki->id,
                    'profile' => json_encode([
                        'audience' => 'wide',
                        'temporality' => 'permanent',
                        'purpose' => 'data_hub',
                    ]),
                ]
            )
            ->assertStatus(200);

        $versionB = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'wiki' => $wiki->id,
                    'profile' => json_encode([
                        'audience' => 'wide',
                        'temporality' => 'temporary',
                        'purpose' => 'data_hub',
                    ]),
                ]
            )
            ->assertStatus(200);

        $this->assertEquals(
            2,
            WikiProfile::where(['wiki_id' => $wiki->id])->count()
        );
        $this->assertEquals(
            'permanent',
            WikiProfile::find($versionA->json()['data']['id'])['temporality']
        );
        $this->assertEquals(
            'temporary',
            WikiProfile::find($versionB->json()['data']['id'])['temporality']
        );
    }
}
