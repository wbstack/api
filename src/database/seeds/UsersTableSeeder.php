<?php

use App\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
          'email' => 'adamshorland@gmail.com',
          'password' => Hash::make('a'),
          'verified' => true,
        ]);
        $user = User::create([
          'email' => 'a@a.a',
          'password' => Hash::make('a'),
          'verified' => true,
        ]);
        $user = User::create([
          'email' => 'b@b.b',
          'password' => Hash::make('b'),
          'verified' => false,
        ]);
        // create 10 users using the user factory
        factory(App\User::class, 3)->create();
    }
}
