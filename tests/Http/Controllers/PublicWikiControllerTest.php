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

    public function testIndexReusePrototypeOnlyRequiresOneAdditionalDatabaseQuery(): void {
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

        $wikiProfilesQueryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$wikiProfilesQueryCount): void {
            if (str_contains($query->sql, 'wiki_profiles')) {
                $wikiProfilesQueryCount++;
            }
        });

        $controller = new PublicWikiController;
        $resourceCollection = $controller->index(new Request);

        $this->assertSame(1, $wikiProfilesQueryCount);
        $this->assertTrue($resourceCollection->first()->relationLoaded('wikiLatestProfile'));
    }

    public function testIndexReusePrototypeIsFalseWhenWikiHasNoLatestProfile(): void {
        $wikiWithoutProfile = Wiki::factory()->create([
            'domain' => 'no-profile.wikibase.cloud',
            'sitename' => 'No Profile Test Site',
        ]);

        $controller = new PublicWikiController;
        $request = new Request;
        $resourceCollection = $controller->index($request);

        $resource = $resourceCollection->firstWhere('id', $wikiWithoutProfile->id);

        $this->assertNotNull($resource);
        $this->assertFalse($resource->toArray($request)['reuse_prototype']);
    }

    public function testIndexReusePrototypeIsFalseWhenWikiIsNotIntendedForReuse(): void {
        $incompleteProfileWiki = Wiki::factory()->create([
            'domain' => 'incomplete-profile.wikibase.cloud',
            'sitename' => 'Incomplete Profile Test Site',
        ]);
        WikiProfile::create([
            'wiki_id' => $incompleteProfileWiki->id,
            'purpose' => 'other',
            'temporality' => 'temporary',
            'audience' => 'other',
        ]);

        $controller = new PublicWikiController;
        $request = new Request;
        $resourceCollection = $controller->index($request);

        $resource = $resourceCollection->firstWhere('id', $incompleteProfileWiki->id);

        $this->assertNotNull($resource);
        $this->assertFalse($resource->toArray($request)['reuse_prototype']);
    }
}
