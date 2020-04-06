<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

/**
 * When new seeders are added to this class it is likely that you will need to run composer dump-autoload
 * as these classes are currently loaded in a class map (not PSR)
 */
class DatabaseSeeder extends Seeder
{

    public function run()
    {
        Model::unguard();
        $this->call(UsersSeeder::class);
        $this->call(InvitationsSeeder::class);
        $this->call(WikiDbsSeeder::class);
        $this->call(QueryserviceNamespacesSeeder::class);
        $this->call(WikisSeeder::class);
        Model::reguard();
    }

}
