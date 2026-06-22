<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('policy_acceptances', function (Blueprint $table) {
            $table->id();

            // Can't use the `foreignId()` method because the `users.id` column isn't an unsigned big integer
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnUpdate()->restrictOnDelete();

            $table->foreignId('policy_id')->constrained()->restrictOnUpdate()->restrictOnDelete();

            // Using a separate `accepted_at` column rather than renaming the default `created_at` column because:
            //   * it reduces confuses by remaining consistent with other tables that use the default columns
            //   * `accepted_at` will be before `created_at` when backfilling the terms-of-use acceptances
            $table->timestamp('accepted_at')->useCurrent();

            // explicitly define the default timestamp columns so that they are not nullable
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('policy_acceptances');
    }
};
