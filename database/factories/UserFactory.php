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
            'email' => $this->faker->unique()->safeEmail(),
            // use the email as the password so that it is easier to log in as these users
            'password' => fn (array $attributes) => $attributes['email'],
            'verified' => $this->faker->boolean(),
        ];
    }
}
