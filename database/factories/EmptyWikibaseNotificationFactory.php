<?php

namespace Database\Factories;

use App\EmptyWikibaseNotification;
use App\Notifications\EmptyWikiNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmptyWikibaseNotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmptyWikibaseNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [];
    }
}
