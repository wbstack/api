<?php

namespace Tests;

use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
use App\Wiki;
use App\WikiDb;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaWikiHostResolverTest extends TestCase {
    use RefreshDatabase;

    public function testResolverRoutesToCorrectHost(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.43-wbs2');
        $resolver = new MediaWikiHostResolver();
        $this->assertEquals(
            'mediawiki-143-app-backend.default.svc.cluster.local',
            $resolver->getBackendHostForDomain($domain)
        );
    }

    public function testResolverBuildsBackendUrl(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.43-wbs2');
        $resolver = new MediaWikiHostResolver();
        $this->assertEquals(
            'http://mediawiki-143-app-backend.default.svc.cluster.local',
            $resolver->getBackendUrlForDomain($domain)
        );
    }

    public function testResolverReturnsAllHostsForDomain(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.43-wbs2');
        $resolver = new MediaWikiHostResolver();

        $this->assertEquals([
            'web' => 'mediawiki-143-app-web.default.svc.cluster.local',
            'backend' => 'mediawiki-143-app-backend.default.svc.cluster.local',
            'api' => 'mediawiki-143-app-api.default.svc.cluster.local',
            'alpha' => 'mediawiki-143-app-alpha.default.svc.cluster.local',
        ], $resolver->getHostsForDomain($domain));
    }

    public function testResolverBuildsBackendUrlForWiki(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $wiki = $this->createWiki($domain, 'mw1.43-wbs2');
        $resolver = new MediaWikiHostResolver();

        $this->assertEquals(
            'http://mediawiki-143-app-backend.default.svc.cluster.local',
            $resolver->getBackendUrlForWiki($wiki)
        );
    }

    public function testResolverThrowsIfUnableToFindHostInMap(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $this->createWiki($domain, 'mw1.43-unmapped');
        $resolver = new MediaWikiHostResolver();
        $this->assertThrows(
            fn () => $resolver->getBackendHostForDomain($domain),
            UnknownDBVersionException::class
        );
    }

    public function testResolverThrowsIfUnableToFindWiki(): void {
        $domain = (new Factory())->create()->unique()->text(30);
        $resolver = new MediaWikiHostResolver();
        $this->assertThrows(
            fn () => $resolver->getBackendHostForDomain($domain),
            UnknownWikiDomainException::class
        );
    }

    private function createWiki(string $domain, string $version): Wiki {
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
}
