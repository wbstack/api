<?php

namespace Tests\Routes\Wiki;

use Tests\TestCase;
use App\Wiki;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetWikiForDomainTest extends TestCase
{
    protected $route = '/backend/wiki/getWikiForDomain';

    use RefreshDatabase;

    public function tearDown(): void
    {
        Wiki::query()->delete();
        parent::tearDown();
    }

    public function testNotFound()
    {
        Wiki::factory()->create(['domain' => 'found.wikibase.cloud']);

        $this->json('GET', $this->route."?domain=notfound.wikibase.cloud")->assertStatus(404);
    }
    public function testFoundOne()
    {
        Wiki::factory()->create(['domain' => 'found.wikibase.cloud']);

        $this->json('GET', $this->route."?domain=found.wikibase.cloud")
            ->assertStatus(200)
            ->assertJsonPath('data.domain', 'found.wikibase.cloud');
    }
}
