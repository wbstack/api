<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tou_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('tou_version', 10);
            $table->timestamp('tou_accepted_at');
            $table->timestamps();
            $table->unique(['user_id', 'tou_version']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tou_acceptances');
    }
};
