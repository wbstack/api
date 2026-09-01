<?php

// TODO: move models and tests to Models namespaces

namespace Tests\Feature\Models;

use App\Enums\ReviewSubmissionActionType;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase {
    use DatabaseTransactions;

    /**
     * A review submission can be created and saved to the database.
     */
    public function testCreation(): void {
        $wiki = Wiki::factory()->create();
        $submission = ReviewSubmission::make([
            'wiki_id' => $wiki->id,
            'additional_information' => 'This ReviewSubmission was created with a Factory. ' . fake()->paragraph(),
        ]);

        $this->assertTrue($submission->save());
    }

    /**
     * A review submission can retrieve all of its actions.
     */
    public function testRetrievingActions(): void {
        $submission = ReviewSubmission::factory()->create();

        ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::SUBMITTED]);

        ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::REVIEW_STARTED]);

        $this->assertCount(2, $submission->actions);
        $this->assertContainsOnlyInstancesOf(ReviewSubmissionAction::class, $submission->actions);
    }

    /**
     * A review submission can retrieve its latest action.
     */
    public function testRetrievingLatestAction(): void {
        $submission = ReviewSubmission::factory()->create();

        ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::SUBMITTED]);

        $latestAction = ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::REVIEW_STARTED]);

        $submission->refresh();

        $this->assertTrue($submission->latestAction->is($latestAction));
    }

    /**
     * A review submission can retrieve its latest action type.
     */
    public function testRetrievingLatestActionType(): void {
        $submission = ReviewSubmission::factory()->create();

        ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::SUBMITTED]);

        ReviewSubmissionAction::factory()
            ->for($submission)
            ->create(['type' => ReviewSubmissionActionType::REVIEW_STARTED]);

        $submission->refresh();

        $this->assertSame(ReviewSubmissionActionType::REVIEW_STARTED, $submission->latestActionType());
    }
}
