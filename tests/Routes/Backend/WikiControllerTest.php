<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiControllerTest extends TestCase {
    use RefreshDatabase;

    protected $route = '/backend/wiki/setDbVersion';

    public function testSuccess() {
        $targetDbVersion = 'mw1.43-wbs1';
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, $targetDbVersion);

        $this->patchJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(200)
            ->assertJson([
                'result' => 'success',
            ]);
    }

    public function testDomainNotfound() {
        $targetDbVersion = 'mw1.43-wbs1';
        $wikiDomain = 'notFound.wikibase.cloud';

        $this->patchJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(404);
    }

    public function testUnknownDbVersion() {
        $targetDbVersion = 'unknownVersion';
        $wikiDomain = 'coffeebase.wikibase.cloud';

        $this->createWiki($wikiDomain, $targetDbVersion);

        $this->patchJson("{$this->route}?domain={$wikiDomain}&dbVersion={$targetDbVersion}")
            ->assertStatus(500);
    }

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
}
