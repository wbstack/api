<?php

namespace Database\Factories;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\ReviewSubmissionAction;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewSubmissionAction>
 */
class ReviewSubmissionActionFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'review_submission_id' => ReviewSubmission::factory(),
            // TODO: this should be the same user as one of the ReviewSubmission::wiki->wikiManagersWithEmail
            'user_id' => User::factory(),
            'actor_role' => UserRole::WIKI_MANAGER,
            'type' => ReviewSubmissionActionType::SUBMITTED,
        ];
    }
}
