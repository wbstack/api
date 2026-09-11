<?php

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
        $this->createAcceptedReviewSubmission();
        $this->createRejectedReviewSubmission();
        $this->createCancelledReviewSubmission();
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

    public function createCancelledReviewSubmission(): void {
        $wiki = Wiki::firstOrCreate([
            'sitename' => 'ReviewSubmissionSeeder::createCancelledReviewSubmission()',
            'domain' => 'ReviewSubmissionSeeder.createCancelledReviewSubmission.wbaas.dev',
            'description' => 'Wiki for ReviewSubmissionSeeder::createCancelledReviewSubmission()',
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
}
