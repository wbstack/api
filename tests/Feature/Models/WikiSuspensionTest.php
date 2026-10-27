<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\Enums\WikiSuspensionReason;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\User;
use App\Wiki;
use App\WikiSuspension;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WikiSuspensionTest extends TestCase {
    use DatabaseTransactions;

    /**
     * A wiki suspension can be created and saved to the database.
     */
    public function testCreation(): void {
        $suspension = WikiSuspension::factory()->make();

        $this->assertTrue($suspension->save());
    }

    /**
     * A wiki suspension can retrieve the actor who performed it.
     */
    public function testRetrievingActor(): void {
        $actor = User::factory()->create();

        $suspensionId = WikiSuspension::factory()
            ->forActor($actor)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspension = WikiSuspension::findOrFail($suspensionId);

        $this->assertTrue($suspension->actor->is($actor));
    }

    /**
     * A wiki suspension can retrieve the wiki it belongs to.
     */
    public function testRetrievingWiki(): void {
        $wiki = Wiki::factory()->create();

        $suspensionId = WikiSuspension::factory()
            ->for($wiki)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspension = WikiSuspension::findOrFail($suspensionId);

        $this->assertTrue($suspension->wiki->is($wiki));
    }

    /**
     * A wiki suspension can retrieve its related review submission action.
     */
    public function testRetrievingReviewSubmissionAction(): void {
        $reviewSubmission = ReviewSubmission::factory()
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $reviewSubmissionAction = $reviewSubmission->latestAction;
        $suspensionId = WikiSuspension::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspension = WikiSuspension::findOrFail($suspensionId);

        $this->assertTrue($suspension->reviewSubmissionAction->is($reviewSubmissionAction));
        $this->assertTrue($suspension->wiki->is($reviewSubmission->wiki));
    }

    /**
     * Only one wiki suspension can be linked to a review submission action.
     */
    public function testOnlyOneSuspensionCanBeLinkedToAReviewSubmissionAction(): void {
        $reviewSubmissionAction = ReviewSubmissionAction::factory()->create();

        WikiSuspension::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create([
                'active_from' => now()->subDay(),
                'reason' => WikiSuspensionReason::HP_VIOLATION,
            ]);

        $this->expectException(QueryException::class);

        WikiSuspension::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create([
                'active_from' => now(),
                'reason' => WikiSuspensionReason::HP_VIOLATION,
            ]);
    }

    /**
     * A wiki suspension casts its reason to WikiSuspensionReason.
     */
    public function testReasonCastsCorrectly(): void {
        $suspension = WikiSuspension::factory()->create([
            'reason' => WikiSuspensionReason::TOU_VIOLATION,
        ]);

        $this->assertSame(WikiSuspensionReason::TOU_VIOLATION, $suspension->reason);
    }

    /**
     * A wiki suspension casts its active date and timestamps to CarbonImmutable.
     */
    public function testDatesAndTimesCastCorrectly(): void {
        $suspension = WikiSuspension::factory()->create();

        $this->assertInstanceOf(CarbonImmutable::class, $suspension->active_from);
        $this->assertInstanceOf(CarbonImmutable::class, $suspension->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $suspension->updated_at);
    }
}
