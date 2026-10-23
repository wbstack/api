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

    public function testLatestSuspensionActionIsNullWithoutAnySuspensionActions(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspensionAction);
    }

    public function testLatestSuspensionActionReturnsLatestSuspensionForTheGivenWiki(): void {
        $wiki = Wiki::factory()->create();
        // Create two suspension actions for $wiki
        WikiSuspensionAction::factory()->for($wiki)->create();
        $latestSuspensionAction = WikiSuspensionAction::factory()->for($wiki)->create();
        // Create a suspension action for a different wiki
        WikiSuspensionAction::factory()->create();

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspensionAction->is($latestSuspensionAction));
    }

    public function testLatestSuspensionActionReturnsSuspensionScheduledInTheFuture(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addWeek()]);
        $latestSuspensionAction = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspensionAction->is($latestSuspensionAction));
    }

    public function testLatestSuspensionActionReturnsSuspensionScheduledInThePast(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        $latestSuspensionAction = WikiSuspensionAction::factory()
            ->for($wiki)
            ->create(['active_from' => now()->subMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspensionAction->is($latestSuspensionAction));
    }

    public function testLatestSuspensionActionIsNullAfterUnsuspension(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create([
                'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
                'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
            ]);

        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspensionAction);
    }

    public function testLatestSuspensionActionReturnsSuspensionAfterUnsuspension(): void {
        $wiki = Wiki::factory()->create();
        WikiSuspensionAction::factory()->for($wiki)->create();
        WikiSuspensionAction::factory()
            ->for($wiki)
            ->create([
                'suspension_type' => WikiSuspensionActionType::UNSUSPEND,
                'reason' => WikiSuspensionActionReason::INDEFINITELY_ALLOWED,
            ]);
        $latestSuspensionAction = WikiSuspensionAction::factory()->for($wiki)->create();

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspensionAction->is($latestSuspensionAction));
    }
}
