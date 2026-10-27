<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WikiSuspensionReason;
use App\ReviewSubmissionAction;
use App\User;
use App\Wiki;
use App\WikiSuspension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiSuspension>
 */
class WikiSuspensionFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'actor_user_id' => null,
            'wiki_id' => Wiki::factory(),
            'review_submission_action_id' => null,
            'active_from' => now(),
            'reason' => WikiSuspensionReason::AUTOMATIC_EXPIRY,
        ];
    }

    public function forActor(User $actor): static {
        return $this->for($actor, 'actor');
    }

    public function resultingFrom(ReviewSubmissionAction $reviewSubmissionAction): static {
        return $this->state([
            'review_submission_action_id' => $reviewSubmissionAction->id,
            'actor_user_id' => $reviewSubmissionAction->actor_user_id,
            'wiki_id' => $reviewSubmissionAction->reviewSubmission->wiki_id,
        ]);
    }
}
