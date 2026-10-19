<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\Enums\WikiSuspensionActionReason;
use App\Enums\WikiSuspensionActionType;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\User;
use App\Wiki;
use App\WikiSuspensionAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WikiSuspensionActionTest extends TestCase {
    use DatabaseTransactions;

    /**
     * A wiki suspension action can be created and saved to the database.
     */
    public function testCreation(): void {
        $suspensionAction = WikiSuspensionAction::factory()->make();

        $this->assertTrue($suspensionAction->save());
    }

    /**
     * A wiki suspension action can retrieve the actor who performed it.
     */
    public function testRetrievingActor(): void {
        $actor = User::factory()->create();

        $suspensionActionId = WikiSuspensionAction::factory()
            ->forActor($actor)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspensionAction = WikiSuspensionAction::findOrFail($suspensionActionId);

        $this->assertTrue($suspensionAction->actor->is($actor));
    }

    /**
     * A wiki suspension action can retrieve the wiki it belongs to.
     */
    public function testRetrievingWiki(): void {
        $wiki = Wiki::factory()->create();

        $suspensionActionId = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspensionAction = WikiSuspensionAction::findOrFail($suspensionActionId);

        $this->assertTrue($suspensionAction->wiki->is($wiki));
    }

    /**
     * A wiki suspension action can retrieve its related review submission action.
     */
    public function testRetrievingReviewSubmissionAction(): void {
        $reviewSubmission = ReviewSubmission::factory()
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $reviewSubmissionAction = $reviewSubmission->latestAction;
        $suspensionActionId = WikiSuspensionAction::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create()
            ->id;

        // Retrieve the model from the database
        $suspensionAction = WikiSuspensionAction::findOrFail($suspensionActionId);

        $this->assertTrue($suspensionAction->reviewSubmissionAction->is($reviewSubmissionAction));
        $this->assertTrue($suspensionAction->wiki->is($reviewSubmission->wiki));
    }

    /**
     * Only one wiki suspension action can be linked to a review submission action.
     */
    public function testOnlyOneSuspensionActionCanBeLinkedToAReviewSubmissionAction(): void {
        $reviewSubmissionAction = ReviewSubmissionAction::factory()->create();

        WikiSuspensionAction::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create([
                'active_from' => now()->subDay(),
                'reason' => WikiSuspensionActionReason::HP_VIOLATION,
            ]);

        $this->expectException(QueryException::class);

        WikiSuspensionAction::factory()
            ->resultingFrom($reviewSubmissionAction)
            ->create([
                'active_from' => now(),
                'reason' => WikiSuspensionActionReason::HP_VIOLATION,
            ]);
    }

    /**
     * A wiki suspension action casts its suspension type to WikiSuspensionActionType.
     */
    public function testSuspensionTypeCastsCorrectly(): void {
        $suspensionAction = WikiSuspensionAction::factory()->create([
            'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
            'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
        ]);

        $this->assertSame(WikiSuspensionActionType::UNSUSPEND, $suspensionAction->suspension_type);
    }

    /**
     * A wiki suspension action casts its reason to WikiSuspensionActionReason.
     */
    public function testReasonCastsCorrectly(): void {
        $suspensionAction = WikiSuspensionAction::factory()->create([
            'reason' => WikiSuspensionActionReason::TOU_VIOLATION,
        ]);

        $this->assertSame(WikiSuspensionActionReason::TOU_VIOLATION, $suspensionAction->reason);
    }

    /**
     * A wiki suspension action casts its active date and timestamps to CarbonImmutable.
     */
    public function testDatesAndTimesCastCorrectly(): void {
        $suspensionAction = WikiSuspensionAction::factory()->create();

        $this->assertInstanceOf(CarbonImmutable::class, $suspensionAction->active_from);
        $this->assertInstanceOf(CarbonImmutable::class, $suspensionAction->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $suspensionAction->updated_at);
    }
}
