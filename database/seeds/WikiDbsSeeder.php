<?php

use App\WikiDb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class WikiDbsSeeder extends Seeder {
    public function run() {
        WikiDb::create($this->getCreateArray(1));
        WikiDb::create($this->getCreateArray(2));
        WikiDb::create($this->getCreateArray(3));
        WikiDb::create($this->getCreateArray(4));
    }

    private function getCreateArray($index) {
        return [
            'name' => 'dbname' . $index,
            'user' => 'dbuser' . $index,
            'password' => 'dbpassword' . $index,
            'version' => Config::get('wbstack.wiki_db_use_version'),
            'prefix' => 'dbprefix' . $index,
        ];
    }
}
