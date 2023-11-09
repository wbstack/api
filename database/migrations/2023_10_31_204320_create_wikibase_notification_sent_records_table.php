<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikibaseNotificationSentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wikibase_notification_sent_records', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('wiki_id')->nullable()->unsigned();
//
//            $table->unsignedInteger('wiki_id');
//            $table->foreign('wiki_id')->references('id')->on('wikis');

            $table->string('notification_type') -> nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wikibase_notification_sent_records');
    }
}
