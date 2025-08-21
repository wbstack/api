<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiNotificationSentRecordsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('wiki_notification_sent_records', function (Blueprint $table) {
            $table->foreign('wiki_id')->references('id')->on('wikis');

            $table->id();
            $table->timestamps();

            $table->integer('wiki_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->string('notification_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('wiki_notification_sent_records');
    }
}
