<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\PublicWikiController;
use App\Http\Resources\PublicWikiResource;
use App\Wiki;
use App\WikiProfile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicWikiControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testShowEagerLoadsWikiLatestProfileForResource(): void {
        $wiki = Wiki::factory()->create([
            'domain' => 'controller-test.wikibase.cloud',
            'sitename' => 'controller-test',
        ]);

        WikiProfile::create([
            'wiki_id' => $wiki->id,
            'purpose' => 'data_hub',
            'temporality' => 'permanent',
            'audience' => 'wide',
        ]);

        $controller = new PublicWikiController;
        $resource = $controller->show($wiki->id);

        $this->assertInstanceOf(PublicWikiResource::class, $resource);
        $this->assertSame(true, $resource->toArray(request())['reuse_prototype']);
    }
}
