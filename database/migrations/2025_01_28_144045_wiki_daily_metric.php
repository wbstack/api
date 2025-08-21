<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('wiki_daily_metrics', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('wiki_id');
            $table->integer('pages');
            $table->boolean('is_deleted');
            $table->date('date');
            $table->timestamps(); // Created at & Updated at
            $table->unique(['wiki_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wiki_daily_metrics');
    }
};
