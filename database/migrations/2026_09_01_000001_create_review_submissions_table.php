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
        Schema::create('review_submissions', function (Blueprint $table) {
            $table->id();
            // Can't use the `foreignId()` method because the `wikis.id` column isn't an unsigned big integer
            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis')
                // Be explicit about the type of constraint restrictions,
                // don't rely on the database's defaults as they could change.
                // It's possible to have these restrict constraints as the Wiki model is currently only soft-deleted,
                // so the `wiki_id` column should never be updated or deleted.
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->text('additional_information')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('review_submissions');
    }
};
