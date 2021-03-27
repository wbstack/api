<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQsBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('qs_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('eventFrom');
            $table->integer('eventTo');
            $table->integer('wiki_id');
            $table->text('entityIds');
            $table->boolean('done');

            $table->index('done');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('qs_batches');
    }
}
