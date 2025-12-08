<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('wiki_daily_metrics', function (Blueprint $table) {
            $table->integer('total_user_count')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wiki_daily_metrics', function (Blueprint $table) {
            $table->dropColumn('total_user_count');
        });
    }
};
