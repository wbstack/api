<?php

use Illuminate\Database\Seeder;

class InvitationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Invitation::class, 3)->create();
    }
}
