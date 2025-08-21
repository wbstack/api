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
     *
     * @return array
     */
    public function definition() {
        return [
            'password' => $this->faker->password(),
            'email' => $this->faker->unique()->safeEmail(),
            'verified' => $this->faker->boolean(),
        ];
    }
}
