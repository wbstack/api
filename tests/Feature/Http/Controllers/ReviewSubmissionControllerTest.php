<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\User;
use App\Wiki;
use App\WikiManager;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function count;

class ReviewSubmissionControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testIndex(): void {
        $wiki = Wiki::factory()->has(
            ReviewSubmission::factory()
                // TODO: can we make ReviewSubmission::factory() create a submissions with the default submitted action?
                // Do we want it to be possible to create invalid models? It might be useful for testing, but may also bite us...
                ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
                ->count(3)
        )
            ->create();

        $otherSubmission = ReviewSubmission::factory()
            // TODO: can we make ReviewSubmission::factory() create a submissions with the default submitted action?
            // Do we want it to be possible to create invalid models? It might be useful for testing, but may also bite us...
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->for(Wiki::factory())
            ->create();

        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions");

        $response->assertStatus(200);
        $this->assertSame(3, count($response->json()['data']));
        // check that unrelated ReviewSubmissions aren't included in the response
        $this->assertNotContains($otherSubmission, $response->json()['data']);
    }

    public function testIndexWithQueryParams(): void {
        $baseTime = CarbonImmutable::parse('2026-01-01 12:00:00');
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
            )->create();

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

        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions?{$queryString}");

        $response->assertStatus(422);
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
        $response = $this->getJson("/v1/wikis/{$wikiId}/review_submissions");

        $response->assertStatus(404);
    }

    /**
     * Test that a review submission is created
     */
    public function testStore(): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();
        WikiManager::factory()->create([
            'wiki_id' => $wiki->id,
            'user_id' => $user->id,
        ]);

        // this test assumes that the Wiki factory hasn't created any review submissions
        $this->assertSame(0, $wiki->reviewSubmissions->count());

        // `->actingAs()` required as ReviewSubmissionActions records the user_id
        $response = $this->actingAs($user)->post("/v1/wikis/{$wiki->id}/review_submissions");

        $response->assertStatus(201);
        $response->assertJsonFragment(['wiki_id' => $wiki->id]);
        $wiki->refresh();
        $this->assertSame($wiki->reviewSubmissions->first()->id, $response->json('id'));
        $this->assertSame(1, ReviewSubmission::findOrFail($response->json('id'))->actions->count());
    }

    public function testShowWithReviewActions(): void {
        $wiki = Wiki::factory()->create();
        $submission = ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
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
        ]);
        $response
            ->assertJsonPath('id', $submission->id)
            ->assertJsonPath('wiki_id', $wiki->id)
            ->assertJsonPath('latest_action.review_submission_id', $submission->id)
            ->assertJsonPath('latest_action.type', ReviewSubmissionActionType::APPROVED->value)
            ->assertJsonPath('latest_action.actor_user_role', UserRole::REVIEW_COMMITTEE_ADMIN->value);
    }

    public function testShowReturnsNotFoundWhenReviewSubmissionDoesNotBelongToWiki(): void {
        $wiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $submission = ReviewSubmission::factory()->for($otherWiki)->create();

        $response = $this->getJson("/v1/wikis/{$wiki->id}/review_submissions/{$submission->id}");

        $response->assertStatus(404);
    }
}
