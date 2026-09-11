<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\Wiki;
use Illuminate\Database\Seeder;

class ReviewSubmissionSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $this->createSubmittedReviewSubmission();
        $this->createReviewStartedReviewSubmission();
        $this->createAcceptedReviewSubmission();
        $this->createRejectedReviewSubmission();
        $this->createSubmittedThenCancelledReviewSubmission();
        $this->createReviewStartedThenCancelledReviewSubmission();
    }

    public function createSubmittedReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createSubmittedReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createSubmittedReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createSubmittedReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
            ]);
        }
    }

    public function createReviewStartedReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createReviewStartedReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createReviewStartedReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createReviewStartedReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                ]),
            ]);
        }
    }

    public function createAcceptedReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createApprovedReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createApprovedReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createApprovedReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::APPROVED,
                ]),
            ]);
        }
    }

    public function createRejectedReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createRejectedReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createRejectedReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createRejectedReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::REJECTED,
                ]),
            ]);
        }
    }

    public function createSubmittedThenCancelledReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createSubmittedThenCancelledReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createSubmittedThenCancelledReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createSubmittedThenCancelledReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::CANCELLED,
                ]),
            ]);
        }
    }

    public function createReviewStartedThenCancelledReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createReviewStartedThenCancelledReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createReviewStartedThenCancelledReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createReviewStartedThenCancelledReviewSubmission()',
        ]);

        $submission = ReviewSubmission::query()->firstOrCreate(['wiki_id' => $wiki->id]);

        if ($submission->wasRecentlyCreated) {
            $submission->actions()->saveMany([
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::SUBMITTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
                    'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                ]),
                ReviewSubmissionAction::factory()->make([
                    'actor_user_role' => UserRole::WIKI_MANAGER,
                    'type' => ReviewSubmissionActionType::CANCELLED,
                ]),
            ]);
        }
    }
}
