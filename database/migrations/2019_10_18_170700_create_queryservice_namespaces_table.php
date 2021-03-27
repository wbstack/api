<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueryservicenamespacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queryservice_namespaces', function (Blueprint $table) {
            $table->increments('id');

            $table->string('namespace', 100)->unique();
            //$table->string('internalHost', 100)->unique();
            $table->string('backend', 100);

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
        Schema::dropIfExists('queryservice_namespaces');
    }
}
