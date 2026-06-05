<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\PublicWikiController;
use App\Http\Resources\PublicWikiResource;
use App\Wiki;
use App\WikiProfile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicWikiControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testShowLoadsWikiLatestProfileForResource(): void {
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
        $this->assertSame(true, $resource->toArray(new Request)['reuse_prototype']);
    }

    public function testIndexEagerLoadsWikiLatestProfileOnceForCollection(): void {
        for ($i = 1; $i <= 3; $i++) {
            $wiki = Wiki::factory()->create([
                'domain' => "index-eager-load-test-{$i}.wikibase.cloud",
                'sitename' => "Index Eager Load Test Site {$i}",
            ]);

            WikiProfile::create([
                'wiki_id' => $wiki->id,
                'purpose' => 'data_hub',
                'temporality' => 'permanent',
                'audience' => 'wide',
            ]);
        }

        $wikiProfileQueryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$wikiProfileQueryCount): void {
            if (str_contains($query->sql, 'wiki_profiles')) {
                $wikiProfileQueryCount++;
            }
        });

        $controller = new PublicWikiController;
        $resourceCollection = $controller->index(new Request);

        $this->assertSame(1, $wikiProfileQueryCount);
        $this->assertTrue($resourceCollection->first()->relationLoaded('wikiLatestProfile'));
    }
}
