<?php

namespace Database\Factories;

use App\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory {
    protected $model = Policy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'policy_type' => $this->faker->randomElement(['terms-of-use', 'hosting-policy']),
            'active_from' => now(),
            'content_vue_file' => fake()->slug() . '.vue',
        ];
    }
}
