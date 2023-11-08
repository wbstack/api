<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\EventPageUpdate;

class EventPageUpdateTitleLength extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_page_updates', function (Blueprint $table) {
            $table->text('title')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        EventPageUpdate::query()->get()->each(function (EventPageUpdate $eventPageUpdate) {
            $eventPageUpdate->update(['title' => substr($eventPageUpdate->title, 0, 14)]);
        });
        Schema::table('event_page_updates', function (Blueprint $table) {
            $table->string('title', 14)->change();
        });
    }
}
