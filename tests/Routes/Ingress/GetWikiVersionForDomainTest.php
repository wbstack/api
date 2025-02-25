<?php

namespace Tests\Routes\Ingress;

use Tests\TestCase;
use App\Wiki;
use App\WikiDb;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetWikiVersionForDomainTest extends TestCase
{
    protected $route = '/backend/ingress/getWikiVersionForDomain';

    use RefreshDatabase;

    public function tearDown(): void
    {
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
            'wiki_id' => $wiki->id
        ]);
    }

    public function testNotFound()
    {
        $this->createWiki('found.wikibase.cloud', 'someVersion');
        $this->json('GET', $this->route . '?domain=notfound.wikibase.cloud')->assertStatus(401);
    }

    public function testFoundVersion()
    {
        $version = 'someVersion';
        $this->createWiki('found.wikibase.cloud', $version);
        $this->createWiki('other.wikibase.cloud', 'otherVersion');
        $this->json('GET', $this->route . '?domain=found.wikibase.cloud')
            ->assertStatus(200)
            ->assertHeader('x-version', $version)
            ->assertContent('1');
    }
}
