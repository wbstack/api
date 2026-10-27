<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\Wiki;
use App\WikiSuspension;
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

    public function testLatestSuspendSuspensionIsNullWithNoSuspensions(): void {
        $wiki = Wiki::factory()->create();

        // Create a suspension for a different wiki to make sure it isn't returned by $wiki->latestSuspension
        WikiSuspension::factory()->create();

        $this->assertNull(Wiki::findOrFail($wiki->id)->latestSuspension);
    }

    public function testLatestSuspendSuspensionReturnsLatestSuspensionForTheGivenWiki(): void {
        $wiki = Wiki::factory()->create();

        // Create two suspensions for $wiki
        WikiSuspension::factory()->for($wiki)->create();
        $latestSuspension = WikiSuspension::factory()->for($wiki)->create();

        // Create a suspension for a different wiki after the $latestSuspension
        WikiSuspension::factory()->create();

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspension->is($latestSuspension));
    }

    public function testLatestSuspendSuspensionActionReturnsSuspensionScheduledInTheFuture(): void {
        $wiki = Wiki::factory()->create();

        WikiSuspension::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addWeek()]);
        $latestSuspension = WikiSuspension::factory()
            ->for($wiki)
            ->create(['active_from' => now()->addMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspension->is($latestSuspension));
    }

    public function testLatestSuspendSuspensionActionReturnsSuspensionScheduledInThePast(): void {
        $wiki = Wiki::factory()->create();

        WikiSuspension::factory()->for($wiki)->create();
        $latestSuspension = WikiSuspension::factory()
            ->for($wiki)
            ->create(['active_from' => now()->subMonth()]);

        $this->assertTrue(Wiki::findOrFail($wiki->id)->latestSuspension->is($latestSuspension));
    }
}
