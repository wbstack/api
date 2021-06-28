<?php

use App\Invitation;
use Illuminate\Database\Seeder;

class InvitationsSeeder extends Seeder
{
    public function run()
    {
        Invitation::create(['code' => 'invite1']);
        Invitation::create(['code' => 'invite2']);
        Invitation::create(['code' => 'invite3']);
        App\Invitation::factory()->create();
    }
}