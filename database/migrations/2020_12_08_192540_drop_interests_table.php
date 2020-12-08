<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateInterestsTable extends Migration{
    public function up(){
        Schema::dropIfExists('interests');
    }

    public function down(){
        // Do nothing
    }
}
