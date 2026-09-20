<?php

namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->password(),
            'verified' => true,
            'is_admin' => 0,
        ];
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static {
        return $this->state(fn (array $attributes) => [
            'verified' => false,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(int $value = 1): static {
        return $this->state(fn (array $attributes) => [
            'is_admin' => $value,
        ]);
    }
}
