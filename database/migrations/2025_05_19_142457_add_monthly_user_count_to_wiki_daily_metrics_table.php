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
            $table->integer('monthly_casual_users')->nullable();
            $table->integer('monthly_active_users')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wiki_daily_metrics', function (Blueprint $table) {
            $table->dropColumn('monthly_casual_users');
            $table->dropColumn('monthly_active_users');
        });
    }
};
