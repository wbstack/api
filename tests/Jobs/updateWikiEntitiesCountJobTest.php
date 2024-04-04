<?php

namespace Tests;

use App\Wiki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class updateWikiEntitiesCountJobTest
{
    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
        Wiki::query()->delete();
    }

    public function tearDown(): void {
        Wiki::query()->delete();
        parent::tearDown();
    }

    public function TestSuccess()
    {
        Wiki::factory()->create([
            'domain' => 'testWiki1.wikibase.cloud'
        ]);
        Wiki::factory()->create([
            'domain' => 'testWiki2.wikibase.cloud'
        ]);
    }
}
