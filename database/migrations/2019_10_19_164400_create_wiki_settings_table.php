<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiSettingsTable extends Migration {
    public function up() {
        Schema::create('wiki_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('value', 200);
            $table->integer('wiki_id')->nullable()->unsigned();

            $table->index('wiki_id');
            $table->unique(['wiki_id', 'name']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('wiki_settings');
    }
}
