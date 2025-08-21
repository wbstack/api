<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('wiki_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('wiki_id');
            $table->foreign('wiki_id')->references('id')->on('wikis');
            $table->enum('purpose', ['data_hub', 'data_lab', 'tool_lab', 'test_drive', 'decide_later', 'other']);
            $table->string('purpose_other')->nullable();
            $table->enum('audience', ['narrow', 'wide', 'other']);
            $table->string('audience_other')->nullable();
            $table->enum('temporality', ['permanent', 'temporary', 'decide_later', 'other']);
            $table->string('temporality_other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wiki_profiles');
    }
};
