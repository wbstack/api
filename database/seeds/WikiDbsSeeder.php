<?php

use App\WikiDb;
use Illuminate\Database\Seeder;

class WikiDbsSeeder extends Seeder
{
    const MW_VERSION = 'mw1.35-wbs1';

    public function run()
    {
        WikiDb::create($this->getCreateArray(1));
        WikiDb::create($this->getCreateArray(2));
        WikiDb::create($this->getCreateArray(3));
        WikiDb::create($this->getCreateArray(4));
    }

    private function getCreateArray($index)
    {
        return [
            'name' => 'dbname'.$index,
            'user' => 'dbuser'.$index,
            'password' => 'dbpassword'.$index,
            'version' => self::MW_VERSION,
            'prefix' => 'dbprefix'.$index,
        ];
    }
}
