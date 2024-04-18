<?php

namespace Database\Factories;

use App\WikiEntitiesCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\WikiEntitiesCount
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class WikiEntitiesCountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = WikiEntitiesCount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
