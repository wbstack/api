<?php

namespace Tests\Routes\Ingress;

use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class getWikiVersionToHostMapForDomainTest extends TestCase {
    protected $route = '/backend/ingress/getWikiVersionToHostMapForDomain';

    use RefreshDatabase;

    protected function tearDown(): void {
        Wiki::query()->delete();
        parent::tearDown();
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

    public function testFoundVersionMap() {
        $version = 'mw1.43-wbs1';
        $expectedHost = '143';
        $this->createWiki('found.wikibase.cloud', $version);
        $this->createWiki('other.wikibase.cloud', 'otherVersion');
        $this->json('GET', $this->route . '?domain=found.wikibase.cloud')
            ->assertStatus(200)
            ->assertHeader('x-host', $expectedHost)
            ->assertJson([
                'host' => $expectedHost,
            ])
            ->assertContent('1');
    }

}
