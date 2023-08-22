<?php

namespace Tests\Routes\Wiki;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\WikiSiteStats;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PublicWikiTest extends TestCase
{
    protected $route = 'wiki';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        WikiSiteStats::query()->delete();
        parent::tearDown();
    }

    public function testEmpty()
    {
        $this->json('GET', $this->route.'/12')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function testBadMethods()
    {
        $wiki = Wiki::factory()->create(['domain' => 'one.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 77]);

        $this->json('DELETE', $this->route.'/'.$wiki->id)
            ->assertStatus(405)
            ->assertJsonStructure(['message']);

        $this->assertEquals(Wiki::where('id', $wiki->id)->count(), 1);

        $this->json('PATCH', $this->route.'/'.$wiki->id, ['domain' => 'foo.wikibase.cloud'])
            ->assertStatus(405)
            ->assertJsonStructure(['message']);

        $this->assertEquals(Wiki::where('id', $wiki->id)->first()->getAttribute('domain'), 'one.wikibase.cloud');

        $this->json('POST', $this->route, ['domain' => 'create.wikibase.cloud'])
            ->assertStatus(405)
            ->assertJsonStructure(['message']);
    }

    public function testGetOne()
    {
        $wiki = Wiki::factory()->create(['domain' => 'one.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 77]);

        Wiki::factory()->create(['domain' => 'two.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 66]);

        $this->json('GET', $this->route.'/'.$wiki->id)
            ->assertStatus(200)
            ->assertJsonPath('data.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.wiki_site_stats.pages', 77);
    }

    public function testGetAll()
    {
        $wiki = Wiki::factory()->create(['domain' => 'one.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 77]);

        $wiki = Wiki::factory()->create(['domain' => 'two.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 66]);

        $this->json('GET', $this->route)
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 77)
            ->assertJsonPath('data.1.domain', 'two.wikibase.cloud')
            ->assertJsonPath('data.1.wiki_site_stats.pages', 66);
    }

    public function testPagination()
    {
        $wiki = Wiki::factory()->create(['domain' => 'one.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 77]);

        $wiki = Wiki::factory()->create(['domain' => 'two.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 66]);

        $wiki = Wiki::factory()->create(['domain' => 'three.wikibase.cloud']);
        WikiSiteStats::factory()->create(['wiki_id' => $wiki->id, 'pages' => 55]);

        $this->json('GET', $this->route.'?per_page=1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'one.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 77)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->json('GET', $this->route.'?per_page=1&page=3')
            ->assertStatus(200)
            ->assertJsonPath('data.0.domain', 'three.wikibase.cloud')
            ->assertJsonPath('data.0.wiki_site_stats.pages', 55)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3);
        }
}
