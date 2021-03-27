<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventPageUpdatesTable extends Migration
{
    public function up()
    {
        Schema::create('event_page_updates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wiki_id');
            // 14 allows 100 billion as an entity id..
            $table->string('title', 14);
            $table->integer('namespace');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_page_updates');
    }
}
