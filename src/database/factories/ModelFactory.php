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
        'password' => password_hash($faker->unique->text, PASSWORD_DEFAULT ),
    ];
});

$factory->defineAs(App\Wiki::class,'nodb' , function (Faker\Generator $faker) {
    return [
        'sitename' => $faker->name,
        'domain' => str_replace( ' ', '_', $faker->unique->text ) . '.wiki.opencura.com',
    ];
});
