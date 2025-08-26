<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QsBatchesAddPendingColumn extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('qs_batches', function (Blueprint $table) {
            $table->timestampTz('pending_since')->nullable()->default(null);
            $table->unsignedInteger('processing_attempts')->default(0);
            $table->boolean('failed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('qs_batches', function (Blueprint $table) {
            $table->dropColumn('pending_since');
            $table->dropColumn('processing_attempts');
            $table->dropColumn('failed');
        });
    }
}
