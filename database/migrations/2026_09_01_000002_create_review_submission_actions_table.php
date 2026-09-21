<?php

declare(strict_types=1);

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
            $table->enum('type', ['submitted', 'review_started', 'approved', 'rejected', 'cancelled']);
            $table->foreignId('review_submission_id')
                ->constrained('review_submissions')
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change
                ->restrictOnUpdate()
                ->restrictOnDelete();
            // Can't use the `foreignId()` method because the `users.id` column isn't an unsigned big integer
            $table->unsignedInteger('actor_user_id');
            $table->foreign('actor_user_id')->references('id')->on('users')
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change
                ->restrictOnUpdate()
                ->restrictOnDelete();
            // `actor_user_role` needs to be persisted here as an actor's user role might change
            $table->enum('actor_user_role', ['wiki_manager', 'review_committee_admin']);
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
