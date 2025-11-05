<?php

namespace Tests;

use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Wiki;
use App\WikiDb;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaWikiHostResolverTest extends TestCase {
    use RefreshDatabase;

    public function testResolverRoutesToCorrectHost(): void {
        $domain = (new Factory)->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.39-wbs1');
        $resolver = new MediaWikiHostResolver;
        $this->assertEquals(
            'mediawiki-139-app-backend.default.svc.cluster.local',
            $resolver->getBackendHostForDomain($domain)
        );
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

    public function testResolverThrowsIfUnableToFindHostInMap(): void {
        $domain = (new Factory)->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.39-unmapped');
        $resolver = new MediaWikiHostResolver;
        $this->assertThrows(
            fn () => $resolver->getBackendHostForDomain($domain),
            UnknownDBVersionException::class
        );
    }
}
