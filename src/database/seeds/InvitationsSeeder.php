<?php

use Illuminate\Database\Seeder;

class InvitationsSeeder extends Seeder
{

    public function run()
    {
        factory(App\Invitation::class, 3)->create();
    }

}
