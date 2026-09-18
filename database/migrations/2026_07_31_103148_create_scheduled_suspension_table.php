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
        Schema::create('scheduled_suspensions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('wiki_id');
            $table->date('active_from');
            $table->enum('reason', ['expiry', 'hp_violation', 'tou_violation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_suspension');
    }
};
