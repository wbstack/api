<?php

namespace Tests\Jobs;

use App\User;
use App\Wiki;
use App\WikiManager;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

class LimitWikiAccessTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        Route::middleware('limit_wiki_access')->get('/endpoint/{wiki?}', function (Request $request) {
            return response()->json([
                'wiki_id' => $request->attributes->get('wiki')->id,
            ]);
        });
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    private function createWikiAndUser(): array {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        return [$wiki, $user];
    }

    public function generateRequestUriAndBody(Wiki $wiki): Generator {
        yield 'query param and empty body' => ["/endpoint?wiki={$wiki->id}", []];
        yield 'path param and empty body' => ["/endpoint/{$wiki->id}", []];
        yield 'no params and body' => ['/endpoint', ['wiki' => $wiki->id]];
    }

    public function testSuccess(): void {
        [$wiki, $user] = $this->createWikiAndUser();

        foreach ($this->generateRequestUriAndBody($wiki) as $name => [$uri, $body]) {
            try {
                $this->actingAs($user)
                    ->json('GET', $uri, $body)
                    ->assertStatus(200)
                    ->assertJson(['wiki_id' => $wiki->id]);
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError("with data set \"{$name}\" failed", $e->getCode(), $e);
            }
        }
    }

    public function testFailOnWrongWikiManager(): void {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);

        foreach ($this->generateRequestUriAndBody($otherWiki) as $name => [$uri, $body]) {
            try {
                $this->actingAs($user)->json('GET', $uri, $body)->assertStatus(403);
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError("with data set \"{$name}\" failed", $e->getCode(), $e);
            }
        }
    }

    public function testFailOnDeletedWiki(): void {
        [$wiki, $user] = $this->createWikiAndUser();
        $wiki->wikiManagers()->delete();
        $wiki->delete();

        foreach ($this->generateRequestUriAndBody($wiki) as $name => [$uri, $body]) {
            try {
                $this->actingAs($user)->json('GET', $uri, $body)->assertStatus(404);
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError("with data set \"{$name}\" failed", $e->getCode(), $e);
            }
        }
    }

    public function testFailOnMissingWiki(): void {
        [$wiki, $user] = $this->createWikiAndUser();

        foreach ($this->generateRequestUriAndBody(new Wiki()) as $name => [$uri, $body]) {
            try {
                $this->actingAs($user)->json('GET', $uri, $body)->assertStatus(422);
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError("with data set \"{$name}\" failed", $e->getCode(), $e);
            }
        }
    }

    public function testFailOnMissingUser(): void {
        [$wiki, $user] = $this->createWikiAndUser();

        foreach ($this->generateRequestUriAndBody($wiki) as $name => [$uri, $body]) {
            try {
                $this->json('GET', $uri, $body)->assertStatus(403);
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError("with data set \"{$name}\" failed", $e->getCode(), $e);
            }
        }
    }
}
