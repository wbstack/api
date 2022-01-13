<?php

namespace Database\Factories;

use App\Wiki;
use Illuminate\Database\Eloquent\Factories\Factory;

class WikiFactory extends Factory
{
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
    public function definition()
    {
        $subDomainSuffix = config('wbstack.subdomain_suffix');

        return [
            'sitename' => $this->faker->name,
            'domain' => str_replace(' ', '_', substr(strtolower($this->faker->unique->text), 0, 11)).$subDomainSuffix,
        ];
    }
}
