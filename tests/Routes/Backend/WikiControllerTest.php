<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiControllerTest extends TestCase {
    use RefreshDatabase;

    const VALID_WIKI_DB_VERSION_STRING_139 = 'mw1.39-wbs1';

    const VALID_WIKI_DB_VERSION_STRING_143 = 'mw1.43-wbs1';

    protected $routeSetDbVersion = '/backend/wiki/setDbVersion';

    protected $routeGetWikiForDomain = '/backend/wiki/getWikiForDomain';

    private function createWiki(string $domain, string $version) {
        $wiki = Wiki::factory()->create(['domain' => $domain]);
        WikiDb::create([
            'name' => $domain,
            'user' => 'someUser',
            'password' => 'somePassword',
            'version' => $version,
            'prefix' => 'somePrefix',
            'wiki_id' => $wiki->id,
        ]);
    }

    public function testSetWikiDbVersionForDomainSuccess() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->postJson("{$this->routeSetDbVersion}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(200)
            ->assertJson([
                'result' => 'success',
            ]);
    }

    public function testSetWikiDbVersionForDomainWikiNotfound() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'notFound.wikibase.cloud';

        $this->postJson("{$this->routeSetDbVersion}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(404);
    }

    public function testSetWikiDbVersionForDomainUnknownDbVersion() {
        $targetDbVersion = 'unknownVersion';
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->postJson("{$this->routeSetDbVersion}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(400);
    }

    public function testSetWikiDbVersionForDomainMissingDbVersion() {
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->postJson("{$this->routeSetDbVersion}?domain={$wikiDomain}")
            ->assertStatus(422);
    }

    public function testSetWikiDbVersionForDomainMissingWikiDomain() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->postJson("{$this->routeSetDbVersion}?dbVersion={$targetDbVersion}")
            ->assertStatus(422);
    }

    public function testGetWikiForDomainMissingWikiDomain() {
        $this->getJson("{$this->routeGetWikiForDomain}")
            ->assertStatus(422);
    }

    public function testGetWikiForDomainWikiNotFound() {
        $this->getJson("{$this->routeGetWikiForDomain}?domain=somewiki")
            ->assertStatus(404);
    }
}
