<?php

namespace Database\Factories;

use App\Wiki;
use Illuminate\Database\Eloquent\Factories\Factory;

class WikiFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Wiki::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        $sitename = $this->faker->unique()->text(30);
        $sitename = strtolower($sitename);
        $sitename = str_replace(' ', '_', $sitename);
        $sitename = str_replace('.', '', $sitename);
        $domain = $sitename . config('wbstack.subdomain_suffix');

        return [
            'sitename' => $sitename,
            'domain' => $domain,
        ];
    }
}
