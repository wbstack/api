<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('complaint_records', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('dispatched_at')->nullable();
            $table->string('name')->nullable();
            $table->string('mail_address')->nullable();
            $table->text('reason');
            $table->text('offending_urls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('complaint_records');
    }
};
