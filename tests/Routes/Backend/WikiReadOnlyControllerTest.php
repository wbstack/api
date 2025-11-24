<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiReadOnlyControllerTest extends TestCase {
    use RefreshDatabase;

    protected string $route = '/backend/setWikiReadOnly';

    public function testItReturns404WhenWikiNotFound() {
        $response = $this->postJson($this->route, [
            'domain' => 'nonexistent.wikibase.cloud',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Wiki not found for domain: nonexistent.wikibase.cloud',
            ]);
    }

    public function testSetWikiToReadOnly() {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewiki.wikibase.cloud',
        ]);

        $response = $this->postJson($this->route, [
            'domain' => 'somewiki.wikibase.cloud',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'domain' => 'somewiki.wikibase.cloud',
                'message' => 'Wiki set to read-only successfully.',
            ]);

        $this->assertSame(
            'This wiki is currently read-only.',
            WikiSetting::whereWikiId($wiki->id)->whereName('wgReadOnly')->first()->value
        );

    }
}
