<?php

use App\Wiki;
use App\WikiLifecycleEvents;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnforceLifecycleEventsConstraint extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $allWikis = Wiki::withTrashed()->get();
        // Albeit `createOrUpdate` was used when creating lifecycle events
        // was used, multiple copies per wiki were created. To clean up before
        // enforcing a unique constraint on database level, this migration
        // deletes all duplicate rows, keeping the latest one only.
        foreach ($allWikis as $wiki) {
            $latestLifecycleEvent = WikiLifecycleEvents::where(['wiki_id' => $wiki->id])
                ->latest()
                ->take(1)
                ->pluck('id');
            WikiLifecycleEvents::where(['wiki_id' => $wiki->id])
                ->whereNotIn('id', $latestLifecycleEvent)
                ->delete();
        }
        // Now that there is a single row per wiki, we can enforce the unique
        // constraint on database level.
        Schema::table('wiki_lifecycle_events', function (Blueprint $table) {
            $table->unique('wiki_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // foreign key constraints need to be disabled as per https://github.com/laravel/framework/issues/13873
        Schema::disableForeignKeyConstraints();
        Schema::table('wiki_lifecycle_events', function (Blueprint $table) {
            // The column name HAS to be wrapped in an array so Laravel can
            // figure out the relation name.
            $table->dropUnique(['wiki_id']);
        });
        Schema::enableForeignKeyConstraints();
    }
}
