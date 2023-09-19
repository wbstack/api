<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiLifecycleEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wiki_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('first_edited')->nullable();
            $table->timestamp('last_edited')->nullable();

            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wiki_lifecycle_events');
    }
}
