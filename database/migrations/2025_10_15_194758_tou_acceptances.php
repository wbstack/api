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
            $table->string('tou_version');
            $table->timestamp('tou_accepted_at');
            $table->timestamps();
            $table->unique(['user_id', 'tou_version']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tou_version')
                ->references('version')
                ->on('tou_versions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tou_acceptances');
    }
};
