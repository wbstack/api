<?php

namespace Database\Factories;

use App\WikiDb;
use Illuminate\Database\Eloquent\Factories\Factory;

class WikiDbFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WikiDb::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->unique()->text(5),
            'prefix' => $this->faker->unique()->text(5),
            'user' => 'root',
            'password' => 'toor',
            'version' => 'seeded',
        ];
    }
}
