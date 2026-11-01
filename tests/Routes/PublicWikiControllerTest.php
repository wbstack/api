<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\PublicWikiController;
use App\Http\Resources\PublicWikiResource;
use App\Wiki;
use App\WikiProfile;
use Generator;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        $controller = new PublicWikiController();
        $resource = $controller->show($wiki->id);

        $this->assertInstanceOf(PublicWikiResource::class, $resource);
        $this->assertSame(true, $resource->toArray(new Request())['reuse_prototype']);
    }

    public static function provideQueryParamsAndErrorExpected(): Generator {
        yield 'default params' => [[], false];
        yield 'sort by sitename ascending' => [['sort' => 'sitename', 'direction' => 'asc'], false];
        yield 'sort by pages descending' => [['sort' => 'pages', 'direction' => 'desc'], false];
        yield 'sort by invalid value' => [['sort' => 'invalid'], true];
        yield 'sort by invalid direction' => [['direction' => 'invalid'], true];
        yield 'is_featured is boolean true' => [['is_featured' => true], false];
        yield 'is_featured is boolean 0' => [['is_featured' => 0], false];
        yield 'is_featured is invalid' => [['is_featured' => 'invalid'], true];
        yield 'is_active is boolean false' => [['is_active' => false], false];
        yield 'is_active is boolean 1' => [['is_active' => 1], false];
        yield 'is_active is invalid' => [['is_active' => 'invalid'], true];
        yield 'per_page is not int' => [['per_page' => 1.2], true];
        yield 'per_page is too low' => [['per_page' => 0], true];
        yield 'per_page is min value' => [['per_page' => 1], false];
        yield 'per_page is max value' => [['per_page' => 100], false];
        yield 'per_page is too high' => [['per_page' => 101], true];
        yield 'page is not int' => [['page' => 2.3], true];
        yield 'page is too low' => [['page' => 0], true];
        yield 'page is min value' => [['page' => 1], false];
    }

    /**
     * @dataProvider provideQueryParamsAndErrorExpected
     */
    public function testIndexQueryParamValidation(array $queryParams, bool $errorExpected): void {
        $controller = new PublicWikiController();
        $request = new Request($queryParams);

        if ($errorExpected) {
            $this->expectException(ValidationException::class);
        }

        $response = $controller->index($request);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $response);
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

        $controller = new PublicWikiController();
        $resourceCollection = $controller->index(new Request());

        $this->assertSame(1, $wikiProfilesQueryCount);
        $this->assertTrue($resourceCollection->first()->relationLoaded('wikiLatestProfile'));
    }

    public function testIndexReusePrototypeIsFalseWhenWikiHasNoLatestProfile(): void {
        $wikiWithoutProfile = Wiki::factory()->create([
            'domain' => 'no-profile.wikibase.cloud',
            'sitename' => 'No Profile Test Site',
        ]);

        $controller = new PublicWikiController();
        $request = new Request();
        $resourceCollection = $controller->index($request);

        $resource = $resourceCollection->firstWhere('id', $wikiWithoutProfile->id);

        $this->assertNotNull($resource);
        $this->assertFalse($resource->toArray($request)['reuse_prototype']);
    }

    public function testIndexReusePrototypeIsFalseWhenWikiIsNotIntendedForReuse(): void {
        $wiki = Wiki::factory()->create([
            'domain' => 'not-intended-for-reuse.wikibase.cloud',
            'sitename' => 'Not Intended for Reuse Test Site',
        ]);
        WikiProfile::create([
            'wiki_id' => $wiki->id,
            'purpose' => 'test_drive',
            'temporality' => 'temporary',
        ]);

        $controller = new PublicWikiController();
        $request = new Request();
        $resourceCollection = $controller->index($request);

        $resource = $resourceCollection->firstWhere('id', $wiki->id);

        $this->assertNotNull($resource);
        $this->assertFalse($resource->toArray($request)['reuse_prototype']);
    }
}
