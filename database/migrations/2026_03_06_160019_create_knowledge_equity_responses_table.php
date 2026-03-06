<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('knowledge_equity_responses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('wiki_id');
            $table
                ->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                // Explicitly use the eloquent default options.
                // restrict rather than cascading chosen to not result in unexpectedly deleting Knowledge Equity Responses when we delete a wiki
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->enum('selectedOption', ['yes', 'no', 'unsure', 'unsaid']);
            $table->string('freeTextResponse', 3000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('knowledge_equity_responses');
    }
};
