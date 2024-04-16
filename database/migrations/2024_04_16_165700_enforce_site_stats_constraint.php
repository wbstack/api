<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnforceSiteStatsConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wiki_site_stats', function (Blueprint $table) {
            $table->unique('wiki_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // foreign key constraints need to be disabled as per https://github.com/laravel/framework/issues/13873
        Schema::disableForeignKeyConstraints();
        Schema::table('wiki_site_stats', function (Blueprint $table) {
            // The column name HAS to be wrapped in an array so Laravel can
            // figure out the relation name.
            $table->dropUnique(['wiki_id']);
        });
        Schema::enableForeignKeyConstraints();
    }
}
