<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiManagersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('wiki_managers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('wiki_id');
            $table->timestamps();
            $table->unique(['user_id', 'wiki_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('wiki_managers');
    }
}
