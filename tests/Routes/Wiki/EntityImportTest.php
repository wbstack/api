<?php

namespace Tests\Routes\Wiki\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\Wiki;
use App\WikiEntityImport;
use App\User;
use App\WikiManager;

class EntityImportTest extends TestCase
{
    protected $route = 'wiki/entityImport';

    use OptionsRequestAllowed, RefreshDatabase;

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

    public function testNoCredentials()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('GET', $this->route.'?wiki='.$wiki->id)
            ->assertStatus(403);
    }
}
