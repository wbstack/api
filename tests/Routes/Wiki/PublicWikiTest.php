<?php

namespace Tests\Routes\Wiki;

use App\Wiki;
use App\WikiProfile;
use App\WikiSetting;
use App\WikiSiteStats;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class PublicWikiTest extends TestCase {
    protected $route = 'wiki';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    protected function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
        WikiProfile::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
    }

    protected function tearDown(): void {
        Wiki::query()->delete();
        WikiProfile::query()->delete();
        WikiSiteStats::query()->delete();
        WikiSetting::query()->delete();
        parent::tearDown();
    }

    public function testEmpty() {
        $this->json('GET', $this->route . '/12')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function testGetOne() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki2 = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki2->id, 'pages' => 66,
        ]);

        $this->json('GET', $this->route . '/' . $wiki->id)
            ->assertStatus(200)
            ->assertJsonPath('data.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.wiki_site_stats.pages', 77);
    }

    public function testGetAll() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 66,
        ]);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 66)
            ->assertJsonPath('data.1.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 77);
    }

    public function testCustomSorting() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 66,
        ]);

        Wiki::factory()->create([
            'domain' => 'nostats.wikibase.cloud', 'sitename' => 'zsite',
        ]);

        $this->json('GET', $this->route . '?sort=pages&direction=desc')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 77)
            ->assertJsonPath('data.1.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 66)
            ->assertJsonPath('data.2.domain', 'nostats.wikibase.cloud')
            ->assertJsonPath('data.2.wiki_site_stats', null);

        $this->json('GET', $this->route . '?direction=desc')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'nostats.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats', null)
            ->assertJsonPath('data.1.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 77)
            ->assertJsonPath('data.2.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.2.wiki_site_stats.pages', 66);

        $this->json('GET', $this->route . '?sort=pages')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'nostats.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats', null)
            ->assertJsonPath('data.1.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 66)
            ->assertJsonPath('data.2.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.2.wiki_site_stats.pages', 77);

        $this->json('GET', $this->route . '?sort=dinosaur')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->json('GET', $this->route . '?direction=random')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function testPagination() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'csite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 66,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'three.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 55,
        ]);

        $this->json('GET', $this->route . '?per_page=1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'three.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 55)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->json('GET', $this->route . '?per_page=1&page=3')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 77)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function testFilterIsFeatured() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'is_featured' => false,
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'is_featured' => true,
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 66,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'three.wikibase.cloud', 'is_featured' => false,
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 55,
        ]);

        $this->json('GET', $this->route . '?is_featured=1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 66)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function testFilterIsActive() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'csite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 77,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 0,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'three.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 55,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'four.wikibase.cloud', 'sitename' => 'dsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id, 'pages' => 1,
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'nostats.wikibase.cloud', 'sitename' => 'zsite',
        ]);

        $this->json('GET', $this->route . '?is_active=1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'three.wikibase.cloud')
            ->assertJsonPath('data.1.domain', 'one.wikibase.cloud')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    public function testLogoUrl() {
        $wiki = Wiki::factory()->create([
            'domain' => 'one.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id,
        ]);
        WikiSetting::factory()->create([
            'wiki_id' => $wiki->id,
            'name' => 'wgLogo',
            'value' => 'https://storage.googleapis.com/wikibase-cloud/foo.bar.png',
        ]);

        $wiki = Wiki::factory()->create([
            'domain' => 'two.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $wiki->id,
        ]);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath(
                'data.0.logo_url',
                'https://storage.googleapis.com/wikibase-cloud/foo.bar.png'
            )
            ->assertJsonPath('data.1.logo_url', null);
    }

    public function testReusePrototype() {
        $reusableWiki = Wiki::factory()->create([
            'domain' => 'reusable.wikibase.cloud', 'sitename' => 'asite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $reusableWiki->id,
        ]);
        WikiProfile::create([
            'wiki_id' => $reusableWiki->id,
            'purpose' => 'data_hub',
            'temporality' => 'permanent',
            'audience' => 'wide',
        ]);

        $nonReusableWiki = Wiki::factory()->create([
            'domain' => 'non-reusable.wikibase.cloud', 'sitename' => 'bsite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $nonReusableWiki->id,
        ]);
        WikiProfile::create([
            'wiki_id' => $nonReusableWiki->id,
            'purpose' => 'other',
            'temporality' => 'other',
            'audience' => 'other',
        ]);

        $noProfileWiki = Wiki::factory()->create([
            'domain' => 'no-profile.wikibase.cloud', 'sitename' => 'csite',
        ]);
        WikiSiteStats::factory()->create([
            'wiki_id' => $noProfileWiki->id,
        ]);

        $this->json('GET', 'reusePrototype')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'reusable.wikibase.cloud')
            ->assertJsonPath('data.0.reuse_prototype', true)
            ->assertJsonPath('data.1.domain', 'non-reusable.wikibase.cloud')
            ->assertJsonPath('data.1.reuse_prototype', false)
            ->assertJsonPath('data.2.domain', 'no-profile.wikibase.cloud')
            ->assertJsonPath('data.2.reuse_prototype', false);

        $this->json('GET', 'reusePrototype/' . $reusableWiki->id)
            ->assertStatus(200)
            ->assertJsonPath('data.reuse_prototype', true);
    }
}
