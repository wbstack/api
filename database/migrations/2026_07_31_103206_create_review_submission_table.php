<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_submissions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('wiki_id');
            $table->enum('status', ['submitted', 'in_review', 'approved', 'rejected', 'cancelled'])->required();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_submission');
    }
};
