<?php

namespace Database\Factories;

use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class WikiManagerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WikiManager::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'user_id' => User::factory(),
            'wiki_id' => Wiki::factory(),
        ];
    }
}
