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
        Schema::create('wiki_entity_imports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis');

            $table->enum('status', ['pending', 'failed', 'success']);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('payload')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_entity_imports');
    }
};
