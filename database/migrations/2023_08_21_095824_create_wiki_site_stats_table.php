<?php

use App\Wiki;

use App\WikiSiteStats;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWikiSiteStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('wiki_site_stats');
        Schema::create('wiki_site_stats', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedBigInteger('pages')->default(0);
            $table->unsignedBigInteger('articles')->default(0);
            $table->unsignedBigInteger('edits')->default(0);
            $table->unsignedBigInteger('images')->default(0);
            $table->unsignedBigInteger('users')->default(0);
            $table->unsignedBigInteger('activeusers')->default(0);
            $table->unsignedBigInteger('admins')->default(0);
            $table->unsignedBigInteger('jobs')->default(0);
            $table->unsignedBigInteger('cirrussearch-article-words')->default(0);

            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });

        Schema::table('wikis', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wiki_site_stats');
        Schema::dropColumns('wikis', ['description', 'is_featured']);
    }
}
