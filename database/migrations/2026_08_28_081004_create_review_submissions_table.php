<?php

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
            // TODO: should this be constrained or would this stop users deleting their wikis without removing all of their review submissions first
            $table->foreign('wiki_id')->references('id')->on('wikis');
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
