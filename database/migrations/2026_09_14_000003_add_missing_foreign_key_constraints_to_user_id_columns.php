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
        Schema::table('user_verification_tokens', function (Blueprint $table) {
            // update column type and add foreign key constraint
            $table->foreignId('user_id')->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_managers', function (Blueprint $table) {
            // update column type and add foreign key constraint
            $table->foreignId('user_id')->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('wiki_notification_sent_records', function (Blueprint $table) {
            // update column type and add foreign key constraint
            $table->foreignId('user_id')->change()
                ->constrained()
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('user_verification_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->integer('user_id')->change();
        });

        Schema::table('wiki_managers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->integer('user_id')->change();
        });

        Schema::table('wiki_notification_sent_records', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->integer('user_id')->unsigned()->change();
        });
    }
};
