<?php

namespace Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaWikiHostControllerTest extends TestCase {
    use RefreshDatabase;

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

    public function testDomainNotfound() {
        $this->getJson('/backend/getWikiHostForDomain?domain=notfound.wikibase.cloud')
            ->assertStatus(404);
    }

    public function testDbVersionNotfound() {
        $this->createWiki('noversion.wikibase.cloud', 'unknownVersion');
        $this->getJson('/backend/getWikiHostForDomain?domain=noversion.wikibase.cloud')
            ->assertStatus(500);
    }

    public function testFoundHost() {
        $expectedHosts = [
            'backend' => 'mediawiki-143-app-backend.default.svc.cluster.local',
            'web' => 'mediawiki-143-app-web.default.svc.cluster.local',
            'api' => 'mediawiki-143-app-api.default.svc.cluster.local',
        ];
        $this->createWiki('found.wikibase.cloud', 'mw1.43-wbs1');
        $this->createWiki('other.wikibase.cloud', 'otherVersion');
        $this->getJson('/backend/getWikiHostForDomain?domain=found.wikibase.cloud')
            ->assertStatus(200)
            ->assertHeader('x-backend-host', $expectedHosts['backend'])
            ->assertHeader('x-web-host', $expectedHosts['web'])
            ->assertHeader('x-api-host', $expectedHosts['api'])
            ->assertJson([
                'backend-host' => $expectedHosts['backend'],
                'web-host' => $expectedHosts['web'],
                'api-host' => $expectedHosts['api'],
                'domain' => 'found.wikibase.cloud',
            ]);
    }
}
