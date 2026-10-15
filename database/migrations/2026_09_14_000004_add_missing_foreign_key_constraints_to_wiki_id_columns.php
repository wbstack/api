<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('event_page_updates', function (Blueprint $table) {
            $table->foreignId('wiki_id')->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('queryservice_namespaces', function (Blueprint $table) {
            $table->foreignId('wiki_id')->nullable()->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_dbs', function (Blueprint $table) {
            $table->foreignId('wiki_id')->nullable()->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_domains', function (Blueprint $table) {
            $table->foreignId('wiki_id')->nullable()->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_managers', function (Blueprint $table) {
            $table->foreignId('wiki_id')->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_settings', function (Blueprint $table) {
            $table->foreignId('wiki_id')->nullable()->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('event_page_updates', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->integer('wiki_id')->change();
        });

        Schema::table('queryservice_namespaces', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->unsignedInteger('wiki_id')->nullable()->change();
        });

        Schema::table('wiki_dbs', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->unsignedInteger('wiki_id')->nullable()->change();
        });

        Schema::table('wiki_domains', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->unsignedInteger('wiki_id')->nullable()->change();
        });

        Schema::table('wiki_managers', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->integer('wiki_id')->change();
        });

        Schema::table('wiki_settings', function (Blueprint $table) {
            $table->dropForeign(['wiki_id']);
            $table->unsignedInteger('wiki_id')->nullable()->change();
        });
    }
};
