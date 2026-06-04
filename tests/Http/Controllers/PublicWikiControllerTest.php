<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\PublicWikiController;
use App\Http\Resources\PublicWikiResource;
use App\Wiki;
use App\WikiProfile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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
        $this->assertTrue($resource->resource->relationLoaded('wikiLatestProfile'));
    }

    public function testIndexEagerLoadsWikiLatestProfileOnceForCollection(): void {
        for ($i = 1; $i <= rand(3, 100); $i++) {
            $wiki = Wiki::factory()->create([
                'domain' => 'index-eager-load-test-' . $i . '.wikibase.cloud',
                'sitename' => 'Index Eager Load Test Site ' . $i,
            ]);

            WikiProfile::create([
                'wiki_id' => $wiki->id,
                'purpose' => 'data_hub',
                'temporality' => 'permanent',
                'audience' => 'wide',
            ]);
        }

        $profileQueryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$profileQueryCount): void {
            if (str_contains($query->sql, 'wiki_profiles')) {
                $profileQueryCount++;
            }
        });

        $controller = new PublicWikiController;
        $resourceCollection = $controller->index(request());

        $this->assertSame(1, $profileQueryCount);
        $this->assertTrue($resourceCollection->collection[0]->resource->relationLoaded('wikiLatestProfile'));
    }
}
