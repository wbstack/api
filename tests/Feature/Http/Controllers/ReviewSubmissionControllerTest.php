<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\User;
use App\Wiki;
use App\WikiManager;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewSubmissionActionFactory;
use Generator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

use function count;

class ReviewSubmissionControllerTest extends TestCase {
    use DatabaseTransactions;

    private static function replaceUriParameters(string $uri, string|int $wikiId, string|int $reviewSubmissionId): string {
        return str_replace(
            ['{wiki_id}', '{review_submission_id}'],
            [$wikiId, $reviewSubmissionId],
            $uri,
        );
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function provideDataForRequest(): Generator {
        yield 'index' => ['GET', '/v1/wikis/{wiki_id}/review_submissions'];
        yield 'store' => ['POST', '/v1/wikis/{wiki_id}/review_submissions'];
        yield 'show' => ['GET', '/v1/wikis/{wiki_id}/review_submissions/{review_submission_id}'];
    }

    /**
     * @return Generator<string, array{string, string, string}>
     */
    public static function provideDataForRequestWithInvalidWikiId(): Generator {
        foreach (self::provideDataForRequest() as $requestName => [$method, $uri]) {
            foreach (self::provideInvalidWikiId() as $wikiIdName => [$wikiId]) {
                yield "{$requestName} with {$wikiIdName}" => [$method, $uri, $wikiId];
            }
        }
    }

    /**
     * @dataProvider provideDataForRequestWithInvalidWikiId
     */
    public function testApiResourceWithInvalidWikiIdFails(string $method, string $uri, string $wikiId): void {
        $user = User::factory()->create(['verified' => true]);

        $uri = self::replaceUriParameters($uri, $wikiId, 1);

        $response = $this->actingAs($user, 'api')->json($method, $uri);

        $response->assertStatus(404);
    }

    /**
     * @dataProvider provideDataForRequest
     */
    public function testApiResourceWithoutAuthenticatedUserFails(string $method, string $uri): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();
        $submission = ReviewSubmission::factory()->for($wiki)->create();

        $uri = $this->replaceUriParameters($uri, $wiki->id, $submission->id);

        $this->json($method, $uri)->assertStatus(401);
    }

    /**
     * @dataProvider provideDataForRequest
     */
    public function testApiResourceWithUnverifiedUserFails(string $method, string $uri): void {
        $user = User::factory()->create(['verified' => false]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();
        $submission = ReviewSubmission::factory()->for($wiki)->create();

        Passport::actingAs($user);
        $uri = $this->replaceUriParameters($uri, $wiki->id, $submission->id);

        $this->json($method, $uri)->assertStatus(403);
    }

    /**
     * @dataProvider provideDataForRequest
     */
    public function testApiResourceWithUserThatIsNotOneOfTheWikiManagersFails(string $method, string $uri): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for(User::factory()->create(['verified' => true]))->for($wiki)->create();
        $submission = ReviewSubmission::factory()->for($wiki)->create();

        Passport::actingAs($user);
        $uri = $this->replaceUriParameters($uri, $wiki->id, $submission->id);

        $this->json($method, $uri)->assertStatus(403);
    }

    public function testIndex(): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()
            ->has(
                ReviewSubmission::factory()
                    // TODO: can we make ReviewSubmission::factory() create a submissions with the default submitted action?
                    // Do we want it to be possible to create invalid models? It might be useful for testing, but may also bite us...
                    ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
                    ->count(3)
            )
            ->create();
        WikiManager::factory()->for($user)->for($wiki)->create();

        $otherSubmission = ReviewSubmission::factory()
            // TODO: can we make ReviewSubmission::factory() create a submissions with the default submitted action?
            // Do we want it to be possible to create invalid models? It might be useful for testing, but may also bite us...
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->for(Wiki::factory())
            ->create();

        Passport::actingAs($user);
        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions");

        $response->assertStatus(200);
        $this->assertSame(3, count($response->json()['data']));
        // check that unrelated ReviewSubmissions aren't included in the response
        // TODO: verify this actually does a test - maybe just check for the 'id'?
        $this->assertNotContains($otherSubmission->toArray(), $response->json()['data']);
    }

    public function testIndexWithQueryParams(): void {
        $baseTime = CarbonImmutable::parse('2026-01-01 12:00:00');
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()
            ->has(
                ReviewSubmission::factory()
                    // make it look like the ReviewSubmissions were created minutes apart
                    ->sequence(fn (Sequence $sequence) => [
                        'created_at' => $baseTime->addMinutes($sequence->index),
                        'updated_at' => $baseTime->addMinutes($sequence->index),
                    ])
                    // TODO: can we make ReviewSubmission::factory() create a submissions with the default submitted action?
                    // Do we want it to be possible to create invalid models? It might be useful for testing, but may also bite us...
                    ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
                    ->count(3)
            )
            ->create();
        WikiManager::factory()->for($user)->for($wiki)->create();

        Passport::actingAs($user);
        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions?sort=created_at&order=asc&per_page=2&page=1");

        $response->assertStatus(200);

        $data = $response->json()['data'];
        $this->assertSame(2, count($data));
        $this->assertThat($data[0]['id'], $this->lessThan($data[1]['id']));
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function provideInvalidIndexQueryString(): Generator {
        yield 'invalid order' => ['order=invalid'];
        yield 'non-numeric per_page' => ['per_page=invalid'];
        yield 'zero per_page' => ['per_page=0'];
        yield 'negative per_page' => ['per_page=-1'];
        yield 'non-numeric page' => ['page=invalid'];
        yield 'zero page' => ['page=0'];
        yield 'negative page' => ['page=-1'];
    }

    /**
     * @dataProvider provideInvalidIndexQueryString
     */
    public function testIndexReturnsValidationErrorForInvalidQueryString(string $queryString): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->for($user)->for($wiki)->create();

        Passport::actingAs($user);
        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions?{$queryString}");

        $response
            ->assertStatus(422)
            ->assertExactJsonStructure(['success', 'message', 'errors'])
            ->assertJsonPath('success', false);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function provideInvalidWikiId(): Generator {
        yield 'nonexistent id' => ['999999999'];
        yield 'zero' => ['0'];
        yield 'negative id' => ['-1'];
        yield 'non-numeric id' => ['invalid'];
    }

    /**
     * @dataProvider provideInvalidWikiId
     */
    public function testIndexReturnsNotFoundForInvalidWikiId(string $wikiId): void {
        Passport::actingAs(User::factory()->create(['verified' => true]));
        $response = $this->getJson("/v1/wikis/{$wikiId}/review_submissions");

        $response
            ->assertStatus(404)
            ->assertExactJsonStructure(['success', 'message'])
            ->assertJsonPath('success', false);
    }

    /**
     * Test that a review submission is created
     */
    public function testStore(): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();

        // this test assumes that the Wiki factory hasn't created any review submissions
        $this->assertSame(0, $wiki->reviewSubmissions->count());

        Passport::actingAs($user);
        $response = $this->postJson("/v1/wikis/{$wiki->id}/review_submissions");

        // check that only one ReviewSubmission and ReviewSubmissionAction were persisted
        $reviewSubmissions = ReviewSubmission::whereWikiId($wiki->id)->get();
        $this->assertCount(1, $reviewSubmissions);
        $reviewSubmission = $reviewSubmissions->first();
        $this->assertCount(1, ReviewSubmissionAction::whereReviewSubmissionId($reviewSubmission->id)->get());

        $response->assertStatus(201)
            ->assertExactJsonStructure([
                'success',
                'data' => [
                    'id',
                    'wiki_id',
                    'additional_information',
                    'created_at',
                    'updated_at',
                    'latest_action' => [
                        'id',
                        'type',
                        'review_submission_id',
                        'actor_user_id',
                        'actor_user_role',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $reviewSubmission->id)
            ->assertJsonPath('data.wiki_id', $wiki->id)
            ->assertJsonPath('data.additional_information', null)
            ->assertJsonPath('data.latest_action.id', $reviewSubmission->latestAction->id)
            ->assertJsonPath('data.latest_action.type', ReviewSubmissionActionType::SUBMITTED->value)
            ->assertJsonPath('data.latest_action.actor_user_id', $user->id)
            ->assertJsonPath('data.latest_action.actor_user_role', UserRole::WIKI_MANAGER->value);
    }

    /**
     * @return Generator<string, array{mixed}>
     */
    public static function provideInvalidAdditionalInformation(): Generator {
        yield 'integer' => [123];
        yield 'boolean' => [true];
        yield 'associative array / json object' => [['not' => 'a string']];
        yield 'sequential array / json array' => [['not', 'a', 'string']];
    }

    /**
     * @dataProvider provideInvalidAdditionalInformation
     */
    public function testStoreWithInvalidAdditionalInformation(mixed $additionalInformation): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();

        Passport::actingAs($user);
        $response = $this->postJson("/v1/wikis/{$wiki->id}/review_submissions", [
            'additional_information' => $additionalInformation,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('additional_information');
    }

    /**
     * @return Generator<string, array{ReviewSubmissionActionFactory}>
     */
    public static function provideClosedReviewSubmissionActionFactories(): Generator {
        yield 'approved' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
            ['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
            ['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
        ]];

        yield 'rejected' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
            ['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
            ['type' => ReviewSubmissionActionType::REJECTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
        ]];

        yield 'cancelled' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
            ['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
            ['type' => ReviewSubmissionActionType::CANCELLED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
        ]];
    }

    /**
     * @dataProvider provideClosedReviewSubmissionActionFactories
     */
    public function testStoreSucceedsIfWikiDoesNotHaveAnOpenReviewSubmission(array $actionStates): void {
        $user = User::factory()->create(['verified' => true]);
        $admin = User::factory()->create(['is_admin' => 1]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();
        $submission = ReviewSubmission::factory()
            ->for($wiki)
            ->has(
                relationship: 'actions',
                factory: ReviewSubmissionAction::factory()
                    ->forEachSequence(
                        ...array_map(function (array $actionState) use ($user, $admin): array {
                            $actor = $actionState['actor_user_role'] === UserRole::WIKI_MANAGER ? $user : $admin;
                            $actionState['actor_user_id'] = $actor->id;

                            return $actionState;
                        }, $actionStates)
                    )
            )
            ->create();

        // assert that the same $user and $wiki are being used for all the models
        $this->assertTrue($submission->actions->first()->actor->is($user));
        $this->assertTrue($submission->wiki->is($wiki));
        // assert that the correct number of review submission actions have been created
        $this->assertSame(count($actionStates), $submission->actions->count());

        // create other users, wikis, and submissions to test that store() is correctly selecting the right submissions
        // TODO: move to a seeder?
        for ($i = 0; $i < 2; $i++) {
            $otherUser = User::factory()->create(['verified' => true]);
            $otherWiki = Wiki::factory()->create();
            WikiManager::factory()->for($otherUser)->for($otherWiki)->create();
            ReviewSubmission::factory()
                ->recycle($otherWiki, $otherUser)
                ->has(ReviewSubmissionAction::factory(), 'actions')
                ->create();
        }

        $response = $this->actingAs($user, 'api')->postJson("/v1/wikis/{$wiki->id}/review_submissions");

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    /**
     * @return Generator<string, array{ReviewSubmissionActionFactory}>
     */
    public static function provideOpenReviewSubmissionActionFactories(): Generator {
        yield 'submitted' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
        ]];

        yield 'review started' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
            ['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
        ]];

        yield 'resubmitted' => [[
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
            ['type' => ReviewSubmissionActionType::CANCELLED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
            ['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER],
        ]];
    }

    /**
     * @dataProvider provideOpenReviewSubmissionActionFactories
     */
    public function testStoreReturnsErrorIfWikiHasOpenReviewSubmission(array $actionStates): void {
        $user = User::factory()->create(['verified' => true]);
        $admin = User::factory()->create(['is_admin' => 1]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();
        $submission = ReviewSubmission::factory()
            ->for($wiki)
            ->has(
                relationship: 'actions',
                factory: ReviewSubmissionAction::factory()
                    ->forEachSequence(
                        ...array_map(function (array $actionState) use ($user, $admin): array {
                            $actor = $actionState['actor_user_role'] === UserRole::WIKI_MANAGER ? $user : $admin;
                            $actionState['actor_user_id'] = $actor->id;

                            return $actionState;
                        }, $actionStates)
                    )
            )
            ->create();

        // assert that the same $user and $wiki are being used for all the models
        $this->assertTrue($submission->actions->first()->actor->is($user));
        $this->assertTrue($submission->wiki->is($wiki));
        // assert that the correct number of review submission actions have been created
        $this->assertSame(count($actionStates), $submission->actions->count());

        $response = $this->actingAs($user, 'api')->postJson("/v1/wikis/{$wiki->id}/review_submissions");

        $response
            ->assertStatus(409)
            ->assertExactJsonStructure(['success', 'message'])
            ->assertJsonPath('success', false);
    }

    public function testShowWithReviewActions(): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();
        $submission = ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        Passport::actingAs($user);
        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'wiki_id',
                'additional_information',
                'latest_action' => [
                    'id',
                    'type',
                    'review_submission_id',
                    'actor_user_role',
                    'actor_user_id',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ],
        ]);
        $response
            ->assertJsonPath('data.id', $submission->id)
            ->assertJsonPath('data.wiki_id', $wiki->id)
            ->assertJsonPath('data.latest_action.review_submission_id', $submission->id)
            ->assertJsonPath('data.latest_action.type', ReviewSubmissionActionType::APPROVED->value)
            ->assertJsonPath('data.latest_action.actor_user_role', UserRole::REVIEW_COMMITTEE_ADMIN->value);
    }

    public function testShowReturnsNotFoundWhenReviewSubmissionDoesNotBelongToWiki(): void {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->for($user)->for($wiki)->create();

        $otherWiki = Wiki::factory()->create();
        $submission = ReviewSubmission::factory()->for($otherWiki)->create();

        Passport::actingAs($user);
        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions/{$submission->id}");

        $response
            ->assertStatus(404)
            ->assertExactJsonStructure(['success', 'message'])
            ->assertJsonPath('success', false);
    }
}
