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
        Schema::create('wiki_entities_counts', function (Blueprint $table) {
            $table->foreign('wiki_id')->references('id')->on('wikis');

            $table->id();
            $table->timestamps();

            $table->integer('items_count')->unsigned();
            $table->integer('properties_count')->unsigned();
            $table->integer('wiki_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_entities_counts');
    }
};
