<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropInterestsTable extends Migration {
    public function up() {
        Schema::dropIfExists('interests');
    }

    public function down() {
        // Do nothing
    }
}
