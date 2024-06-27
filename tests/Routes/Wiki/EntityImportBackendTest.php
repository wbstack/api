<?php

namespace Tests\Routes\Wiki\Managers;

use App\WikiEntityImportStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;
use App\Wiki;
use App\WikiEntityImport;
use App\WikiManager;

class EntityImportBackendTest extends TestCase
{
    protected $route = 'backend/wiki/updateEntityImport';

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiManager::query()->delete();
        WikiEntityImport::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiManager::query()->delete();
        WikiEntityImport::query()->delete();
        parent::tearDown();
    }

    public function testNoUpdate()
    {
        $this->json('PATCH', $this->route, ['wiki_entity_import' => 789, 'status' => 'success'])
            ->assertStatus(404);
    }

    public function testBadStatus()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $import = WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Pending,
            'wiki_id' => $wiki->id,
        ]);
        $this->json('PATCH', $this->route."?wiki_entity_import=".$import->id, ['status' => 'finished'])
            ->assertStatus(400);
    }

    public function testUpdate()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $import = WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Pending,
            'wiki_id' => $wiki->id,
        ]);
        $this->json('PATCH', $this->route."?wiki_entity_import=".$import->id, ['status' => 'success'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'success');
    }
}
