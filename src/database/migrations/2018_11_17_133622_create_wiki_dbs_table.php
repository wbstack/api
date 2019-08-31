<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWikidbsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wiki_dbs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 100);
            $table->string('prefix', 100);
            $table->string('user', 100);
            $table->string('password', 100);

            // Require the dbname and prefix to be unique...
            $table->unique(['name', 'prefix']);

            $table->string('version', 20);
            // Index needed so that we can easily query what needs to be updated
            $table->index( 'version');

            $table->integer('wiki_id')->nullable()->unsigned()->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wiki_dbs');
    }
}
