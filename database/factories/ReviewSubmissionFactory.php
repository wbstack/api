<?php

namespace Database\Factories;

use App\ReviewSubmission;
use App\Wiki;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewSubmission>
 */
class ReviewSubmissionFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'wiki_id' => Wiki::factory(),
            'additional_information' => 'This ReviewSubmission was created with a Factory. ' . fake()->paragraph(),
        ];
    }
}
