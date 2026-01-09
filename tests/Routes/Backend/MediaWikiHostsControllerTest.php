<?php

namespace Tests\Routes\Backend;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaWikiHostsControllerTest extends TestCase {
    use RefreshDatabase;

    protected $route = '/backend/getWikiHostsForDomain';

    public function testSuccess() {
        $expectedHosts = [
            'backend' => 'mediawiki-143-app-backend.default.svc.cluster.local',
            'web' => 'mediawiki-143-app-web.default.svc.cluster.local',
            'api' => 'mediawiki-143-app-api.default.svc.cluster.local',
            'alpha' => 'mediawiki-143-app-alpha.default.svc.cluster.local',
        ];

        $this->createWiki('test139.wikibase.cloud', 'mw1.39-wbs1');
        $this->createWiki('test143.wikibase.cloud', 'mw1.43-wbs2');

        $this->getJson("$this->route?domain=test143.wikibase.cloud")
            ->assertStatus(200)
            ->assertHeader('x-backend-host', $expectedHosts['backend'])
            ->assertHeader('x-web-host', $expectedHosts['web'])
            ->assertHeader('x-api-host', $expectedHosts['api'])
            ->assertHeader('x-alpha-host', $expectedHosts['alpha'])
            ->assertJson([
                'domain' => 'test143.wikibase.cloud',
                'backend-host' => $expectedHosts['backend'],
                'web-host' => $expectedHosts['web'],
                'api-host' => $expectedHosts['api'],
                'alpha-host' => $expectedHosts['alpha'],
            ]);
    }

    public function testDomainNotfound() {
        $this->getJson("$this->route?domain=notfound.wikibase.cloud")
            ->assertStatus(404);
    }

    public function testUnknownDbVersion() {
        $this->createWiki('test.wikibase.cloud', 'unknownVersion');

        $this->getJson("$this->route?domain=test.wikibase.cloud")
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
