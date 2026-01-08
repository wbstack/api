<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('tou_acceptances', function (Blueprint $table) {
            $table->string('tou_version', 10)->change();
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
