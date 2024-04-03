<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiDeletionReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wiki_deletion_reasons', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis');
            $table->text('deletion_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wiki_deletion_reasons');
    }
}
