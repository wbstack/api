<?php

use App\QsCheckpoint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQsCheckpointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qs_checkpoints', function (Blueprint $table) {
            // This does not use the `id` method as it would mean we
            // get auto-increment, which is not what we want in this case
            $table->integer('id')->unsigned()->primary();
            $table->integer('checkpoint')->unsigned();
            $table->timestamps();
        });

        Schema::table('qs_batches', function (Blueprint $table) {
            $table->dropColumn('eventFrom');
            $table->dropColumn('eventTo');
        });

        QsCheckpoint::init();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qs_checkpoints');

        Schema::table('qs_batches', function (Blueprint $table) {
            $table->integer('eventFrom')->default(0);
            $table->integer('eventTo')->default(0);
        });
    }
}
