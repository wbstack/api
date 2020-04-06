<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        User::create([
          'email' => 'adamshorland@gmail.com',
          'password' => Hash::make('a'),
          'verified' => true,
        ]);
        User::create([
          'email' => 'a@a.a',
          'password' => Hash::make('a'),
          'verified' => true,
        ]);
        User::create([
          'email' => 'b@b.b',
          'password' => Hash::make('b'),
          'verified' => false,
        ]);
        // create 10 users using the user factory
        factory(App\User::class, 3)->create();
    }
}
