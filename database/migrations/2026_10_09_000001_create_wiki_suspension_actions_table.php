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
        Schema::create('wiki_suspension_actions', function (Blueprint $table) {
            $table->id();

            $table->enum('suspension_type', ['suspend', 'unsuspend']);

            // Can't use the `foreignId()` method because the `users.id` column isn't an unsigned big integer
            $table->unsignedInteger('actor_user_id')
                // Some suspensions will be created automatically (e.g. when a wiki is created)
                // where there is no sensible user to be linked with the suspension action
                ->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users')
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change
                ->restrictOnUpdate()
                ->restrictOnDelete();

            // An `actor_user_role` column is not required as only 'review_committee_admin's can suspend a wiki

            // Can't use the `foreignId()` method because the `wiki.id` column isn't an unsigned big integer
            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis')
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('review_submission_action_id')
                ->nullable()
                ->unique()
                ->constrained()
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('active_from');

            $table->enum('reason', [
                // for suspension_type = suspend
                'manual_expiry',
                'automatic_expiry',
                'tou_violation',
                'hp_violation',
                // for suspension_type = unsuspend
                'indefinitely_allowed',
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wiki_suspension_actions');
    }
};
