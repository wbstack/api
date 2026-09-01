<?php

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionActionTest extends TestCase {
    use DatabaseTransactions;

    /**
     * A review submission action can be created and saved to the database.
     */
    public function testCreation(): void {
        $action = ReviewSubmissionAction::factory()->make();

        $this->assertTrue($action->save());
    }

    /**
     * A review submission action can retrieve the review submission it belongs to.
     */
    public function testRetrievingReviewSubmission(): void {
        $submission = ReviewSubmission::factory()->create();

        $action = ReviewSubmissionAction::factory()
            ->for($submission)
            ->create();

        $this->assertTrue($action->reviewSubmission->is($submission));
    }

    /**
     * A review submission action casts its actor_role attribute to a UserRole.
     */
    public function testActorRoleCast(): void {
        $action = ReviewSubmissionAction::factory()->create([
            'actor_role' => UserRole::WIKI_MANAGER,
        ]);

        $this->assertSame(UserRole::WIKI_MANAGER, $action->actor_role);
    }

    /**
     * A review submission action casts its type to ReviewSubmissionActionType.
     */
    public function testTypeCast(): void {
        $action = ReviewSubmissionAction::factory()->create([
            'type' => ReviewSubmissionActionType::REVIEW_STARTED,
        ]);

        $this->assertSame(ReviewSubmissionActionType::REVIEW_STARTED, $action->type);
    }

    /**
     * A review submission action casts its timestamps to CarbonImmutable.
     */
    public function testTimestampCasts(): void {
        $action = ReviewSubmissionAction::factory()->create();

        $this->assertInstanceOf(CarbonImmutable::class, $action->created_at);
        $this->assertInstanceOf(CarbonImmutable::class, $action->updated_at);
    }
}
