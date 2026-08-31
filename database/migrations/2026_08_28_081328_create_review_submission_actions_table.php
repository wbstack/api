<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('review_submission_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_submission_id')
                ->constrained('review_submissions')
                // Be explicit about the type of constraints
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('user_id');
            $table->enum('actor_role', ['wiki_manager', 'review_committee_admin']);
            $table->enum('type', ['submitted', 'review_started', 'approved', 'rejected', 'cancelled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('review_submission_actions');
    }
};
