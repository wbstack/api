<?php

namespace Database\Factories;

use App\WikiSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class WikiSettingFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WikiSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [];
    }
}
