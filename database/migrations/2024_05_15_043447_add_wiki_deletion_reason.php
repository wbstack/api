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
        Schema::table('wikis', function (Blueprint $table) {
            $table->string('wiki_deletion_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wikis', function (Blueprint $table) {
            $table->dropColumn('wiki_deletion_reason');
        });
    }
};
