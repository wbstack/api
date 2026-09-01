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
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they might change
                ->restrictOnUpdate()
                ->restrictOnDelete();
            // TODO: should this be constrained? Even if we use `noActionOnUpdate()` and `noActionOnDelete()` adding
            // the foreign key constraint will prevent any random number that isn't a `user_id` from being inserted
            // https://laravel.com/framework/docs/11.x/migrations#foreign-key-constraints
            $table->unsignedInteger('actor_user_id');
            $table->foreign('actor_user_id')->references('id')->on('users');
            // $table->foreignId('actor_user_id')
            //     ->constrained('users')
            //     // Be explicit about the type of constraint restrictions,
            //     // don't rely on the database's defaults as they might change
            //     ->restrictOnUpdate()
            //     ->restrictOnUpdate();
            // this needs to be persisted here as an actor's user role might change
            $table->enum('actor_user_role', ['wiki_manager', 'review_committee_admin']);
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
