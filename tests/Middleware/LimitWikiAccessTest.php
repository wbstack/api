<?php

namespace Tests\Jobs;

use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LimitWikiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Route::middleware('limit_wiki_access')->get('/endpoint', function (Request $request) {
            return response()->json([
                'wiki_id' => $request->attributes->get('wiki')->id
            ]);
        });
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    private function createWikiAndUser(): array
    {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        return array($wiki, $user);
    }

    private function getURI(Wiki $wiki): string
    {
        return "/endpoint?wiki={$wiki->id}";
    }

    public function testSuccess(): void
    {
        [$wiki, $user] = $this->createWikiAndUser();

        $this->actingAs($user)
        ->json('GET', $this->getURI($wiki))
        ->assertStatus(200)
        ->assertJson(['wiki_id' => $wiki->id]);
    }

    public function testFailOnWrongWikiManager(): void
    {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $this->actingAs($user)->json('GET', $this->getURI($otherWiki))->assertStatus(403);
    }

    public function testFailOnDeletedWiki(): void
    {
        [$wiki, $user] = $this->createWikiAndUser();
        $wiki->delete();
        $this->actingAs($user)->json('GET', $this->getURI($wiki))->assertStatus(404);
    }

    public function testFailOnMissingWiki(): void
    {
        [$wiki, $user] = $this->createWikiAndUser();
        $this->actingAs($user)->json('GET', '/endpoint')->assertStatus(422);
    }

    public function testFailOnMissingUser(): void
    {
        [$wiki, $user] = $this->createWikiAndUser();
        $this->json('GET', $this->getURI($wiki))->assertStatus(403);
    }
}
