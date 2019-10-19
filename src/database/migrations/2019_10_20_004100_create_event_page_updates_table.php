<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventPageUpdatesTable extends Migration
{

    public function up()
    {
        Schema::create('event_page_updates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('site', 100);
            $table->string('title', 100);
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
