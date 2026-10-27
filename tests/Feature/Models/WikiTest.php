<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\Wiki;
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
}
