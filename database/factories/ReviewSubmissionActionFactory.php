<?php

declare(strict_types=1);

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
            // TODO: this should be the same User as `ReviewSubmission::actions->first()->actor`
            // and one of the `ReviewSubmission::wiki->wikiManagersWithEmail`.
            // Can likely be achieved with an anonymous method as demonstrated in the Laravel docs:
            // https://laravel.com/framework/docs/11.x/eloquent-factories#defining-relationships-within-factories
            'actor_user_id' => User::factory(),
            'actor_user_role' => UserRole::WIKI_MANAGER,
            'type' => ReviewSubmissionActionType::SUBMITTED,
        ];
    }

    public function forActor(User $actor): static {
        return $this->for($actor, 'actor');
    }
}
