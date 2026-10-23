<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\Enums\WikiSuspensionActionReason;
use App\Enums\WikiSuspensionActionType;
use App\ReviewSubmission;
use App\Wiki;
use App\WikiSuspensionAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WikiTest extends TestCase {
    use DatabaseTransactions;

    public function testSubmittedReviewSubmissionIsPending(): void {
        $wiki = Wiki::factory()->create();
        $reviewSubmission = ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->create();

        $this->assertTrue(ReviewSubmission::findOrFail($reviewSubmission->id)->is($wiki->pendingReviewSubmission));
    }

    public function testReviewStartedReviewSubmissionIsPending(): void {
        $wiki = Wiki::factory()->create();
        $reviewSubmission = ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $this->assertTrue(ReviewSubmission::findOrFail($reviewSubmission->id)->is($wiki->pendingReviewSubmission));
    }

    public function testApprovedReviewSubmissionIsNotPending(): void {
        $wiki = Wiki::factory()->create();
        ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::APPROVED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->pendingReviewSubmission);
    }

    public function testRejectedReviewSubmissionIsNotPending(): void {
        $wiki = Wiki::factory()->create();
        ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::REVIEW_STARTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->hasActions(['type' => ReviewSubmissionActionType::REJECTED, 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN])
            ->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->pendingReviewSubmission);
    }

    public function testCancelledReviewSubmissionIsNotPending(): void {
        $wiki = Wiki::factory()->create();
        ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(['type' => ReviewSubmissionActionType::SUBMITTED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->hasActions(['type' => ReviewSubmissionActionType::CANCELLED, 'actor_user_role' => UserRole::WIKI_MANAGER])
            ->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->pendingReviewSubmission);
    }

    public function testLatestSuspendSuspensionActionIsNullWithoutAnySuspensionActions(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction);
    }

    public function testLatestSuspendSuspensionActionReturnsLatestSuspensionForTheGivenWiki(): void {
        $wiki = Wiki::factory()->create();
        // Create two suspension actions for $wiki
        WikiSuspensionAction::factory()->for($wiki)->create();
        $latestSuspendSuspensionAction = WikiSuspensionAction::factory()->for($wiki)->create();
        // Create a suspension action for a different wiki
        WikiSuspensionAction::factory()->create();

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($latestSuspendSuspensionAction));
    }

    public function testLatestSuspendSuspensionActionReturnsSuspensionScheduledInTheFuture(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addWeek()]);
        $latestSuspendSuspensionAction = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($latestSuspendSuspensionAction));
    }

    public function testLatestSuspendSuspensionActionReturnsSuspensionScheduledInThePast(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        $latestSuspendSuspensionAction = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->subMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($latestSuspendSuspensionAction));
    }

    public function testLatestSuspendSuspensionActionReturnsWHATWhenSuspensionIsAFutureUnsuspend(): void {
        $wiki = Wiki::factory()->create();
        $suspendSuspensionAction = WikiSuspensionAction::factory()->for($wiki)->create();
        $unsuspendSuspensionAction = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create([
                'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
                'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
                'active_from' => now()->addMonth(),
            ]);

        // This is what is currently returned, will we end up making a Wiki available before $unsuspendSuspensionAction->active_from?
        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction);
        // This is what should be returned if you take the name of the relationship at face value
        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($suspendSuspensionAction));
        // I'm not sure if returning this would help, we would then need to look at the next suspension action...
        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($unsuspendSuspensionAction));
        // Things that might make it easier to deal with:
        //   * a wiki is suspended due to expiry unless marked otherwise
        //   * we don't allow suspension actions of type UNSUSPEND have an active_from date in the future - how best to enforce this?
    }

    public function testLatestSuspendSuspensionActionIsNullAfterUnsuspension(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create([
                'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
                'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
            ]);

        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction);
    }

    public function testLatestSuspendSuspensionActionReturnsSuspensionAfterUnsuspension(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create([
                'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
                'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
            ]);
        $latestSuspendSuspensionAction = WikiSuspensionAction::factory()->for($wiki)->create();

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspendSuspensionAction->is($latestSuspendSuspensionAction));
    }
}
