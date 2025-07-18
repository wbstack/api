<?php

namespace Database\Factories;

use App\ComplaintRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\ComplaintRecord
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class ComplaintRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = ComplaintRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispatched' => null,
        ];
    }
}
