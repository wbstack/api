<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->email,
        'password' => password_hash(substr($faker->unique->text, 0, 10), PASSWORD_DEFAULT),
        'verified' => false,
    ];
});

$factory->define(App\Invitation::class, function (Faker\Generator $faker) {
    return [
        'code' => strtolower(substr($faker->unique->text, 0, 8)),
    ];
});

$factory->define(App\Wiki::class, function (Faker\Generator $faker) {
    return [
        'sitename' => $faker->name,
        'domain' => str_replace(' ', '_', substr(strtolower($faker->unique->text), 0, 11)).'.wiki.opencura.com',
    ];
});

$factory->define(App\WikiManager::class, function (Faker\Generator $faker) {
    // Attributes must be defined byt the caller
    return [];
});
