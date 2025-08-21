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
            $table->integer('daily_actions')->nullable();
            $table->integer('weekly_actions')->nullable();
            $table->integer('monthly_actions')->nullable();
            $table->integer('quarterly_actions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wiki_daily_metric', function (Blueprint $table) {});
    }
};
