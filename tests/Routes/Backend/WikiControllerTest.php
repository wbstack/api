<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiControllerTest extends TestCase {
    use RefreshDatabase;

    protected $route = '/backend/wiki/getWikiForDomain';

    private function createWiki(string $domain) {
        $wiki = Wiki::factory()->create(['domain' => $domain]);
        WikiDb::create([
            'name' => $domain,
            'user' => 'someUser',
            'password' => 'somePassword',
            'prefix' => 'somePrefix',
            'version' => 'someVersion',
            'wiki_id' => $wiki->id,
        ]);
    }

    public function testGetWikiDomainSuccess() {
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain);

        $this->get("{$this->route}?domain={$wikiDomain}")
            ->assertStatus(200)
            ->assertJsonPath('data.domain', $wikiDomain)
            ->assertJsonStructure([
                'data' => [
                    'domain',
                    'sitename',
                    'wiki_queryservice_namespace',
                ],
            ]);
    }

    public function testGetWikiDomainMissingWikiDomain() {
        $this->getJson("{$this->route}")
            ->assertStatus(422);
    }

    public function testGetWikiDomainWikiNotFound() {
        $this->getJson("{$this->route}?domain=somewiki")
            ->assertStatus(404);
    }
}
