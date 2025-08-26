<?php

use App\QueryserviceNamespace;
use Illuminate\Database\Seeder;

class QueryserviceNamespacesSeeder extends Seeder {
    public function run() {
        QueryserviceNamespace::create($this->getCreateArray(1));
        QueryserviceNamespace::create($this->getCreateArray(2));
        QueryserviceNamespace::create($this->getCreateArray(3));
        QueryserviceNamespace::create($this->getCreateArray(4));
    }

    private function getCreateArray($index) {
        return [
            'namespace' => 'qsnamespace' . $index,
            'backend' => 'someQueryserviceBackend',
        ];
    }
}
