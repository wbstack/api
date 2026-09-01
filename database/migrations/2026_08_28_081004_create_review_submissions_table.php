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
            //
            // As the Wiki model is only soft-deleted, where the `wiki_id` column is not updated or deleted,
            // it is possible to have a foreign key constraint and soft-delete a wiki.
            // https://stackoverflow.com/questions/73787579/how-to-cascade-soft-deletes-in-laravel
            //
            // If/when we come to hard deleting some/all of the Wiki's data we might want to rethink this,
            // but let's do that at the time when we have more information about our requirements.
            //
            // Even if we use `noActionOnUpdate()` and `noActionOnDelete()` adding the foreign key constraint
            // will prevent any random number that isn't a `wiki_id` from being inserted
            // https://laravel.com/framework/docs/11.x/migrations#foreign-key-constraints
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
