<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiDbVersionControllerTest extends TestCase {
    use RefreshDatabase;

    const VALID_WIKI_DB_VERSION_STRING_139 = 'mw1.39-wbs1';

    const VALID_WIKI_DB_VERSION_STRING_143 = 'mw1.43-wbs1';

    protected $route = '/backend/setWikiDbVersion';

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

        return $wiki;
    }

    public function testSetWikiDbVersionSuccess() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $wiki = $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->putJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(200)
            ->assertJson([
                'result' => 'success',
            ]);

        $newDbVersion = Wiki::with('wikiDb')->firstWhere('id', $wiki->id)->wikiDb->version;

        $this->assertSame($targetDbVersion, $newDbVersion);
    }

    public function testSetWikiDbVersionWikiNotfound() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'notFound.wikibase.cloud';

        $this->putJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(404);
    }

    public function testSetWikiDbVersionUnknownDbVersion() {
        $targetDbVersion = 'unknownVersion';
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->putJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(400);
    }

    public function testSetWikiDbVersionMissingDbVersion() {
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->putJson("{$this->route}?domain={$wikiDomain}")
            ->assertStatus(422);
    }

    public function testSetWikiDbVersionMissingWikiDomain() {
        $targetDbVersion = self::VALID_WIKI_DB_VERSION_STRING_143;
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, self::VALID_WIKI_DB_VERSION_STRING_139);

        $this->putJson("{$this->route}?dbVersion={$targetDbVersion}")
            ->assertStatus(422);
    }
}
