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
        $response = $this->putJson($this->route, [
            'domain' => 'nonexistent.wikibase.cloud',
            'readOnly' => true,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => "Wiki not found for domain 'nonexistent.wikibase.cloud'",
            ]);
    }

    public function testSetWikiToReadOnly() {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewiki.wikibase.cloud',
        ]);

        $response = $this->putJson($this->route, [
            'domain' => 'somewiki.wikibase.cloud',
            'readOnly' => true,
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

    public function testDeleteSettingForReadOnlyFalse() {
        $wiki = Wiki::factory()->create([
            'domain' => 'somewiki.wikibase.cloud',
        ]);
        $wiki->setSetting('wgReadOnly', 'test');

        $this->putJson($this->route, [
            'domain' => $wiki->domain,
            'readOnly' => false,
        ])
            ->assertStatus(200)
            ->assertJson(['message' => 'Read-only setting successfully removed for wiki.']);

        $this->assertNull(
            WikiSetting::whereWikiId($wiki->id)
                ->whereName('wgReadOnly')
                ->first(),
        );
    }
}
