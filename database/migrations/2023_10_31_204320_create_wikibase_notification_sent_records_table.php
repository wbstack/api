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
            $table->foreign('wiki_id')->references('id')->on('wikis');

            $table->id();
            $table->timestamps();

            $table->integer('wiki_id')->unsigned();

            $table->string('notification_type');
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
