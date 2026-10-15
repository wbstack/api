<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    private const TABLES_WITH_FOREIGN_KEY_CONSTRAINTS = [
        'knowledge_equity_responses',
        'wiki_entity_imports',
        'wiki_lifecycle_events',
        'wiki_notification_sent_records',
        'wiki_profiles',
        'wiki_site_stats',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void {
        /**
         * DROP EXISTING FOREIGN KEY CONSTRAINTS
         */
        foreach (self::TABLES_WITH_FOREIGN_KEY_CONSTRAINTS as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['wiki_id']);
            });
        }

        /**
         * UPDATE PRIMARY KEY COLUMN TYPE
         */
        Schema::table('wikis', function (Blueprint $table) {
            $table->id()->change();
        });

        /**
         * UPDATE FOREIGN KEY COLUMN TYPE AND RECREATE DROPPED CONSTRAINTS
         */
        foreach (self::TABLES_WITH_FOREIGN_KEY_CONSTRAINTS as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('wiki_id')
                    ->change()
                    ->constrained()
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        /**
         * DROP EXISTING FOREIGN KEY CONSTRAINTS
         */
        foreach (self::TABLES_WITH_FOREIGN_KEY_CONSTRAINTS as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['wiki_id']);
            });
        }

        /**
         * REVERSE PRIMARY KEY COLUMN TYPE CHANGE
         */
        Schema::table('wikis', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        /**
         * REVERSE FOREIGN KEY COLUMN TYPE CHANGE AND RECREATE DROPPED CONSTRAINTS
         *
         * Can't use a foreach loop as not all `wiki_id` columns were created the same
         */
        Schema::table('knowledge_equity_responses', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
            $table->foreign('wiki_id')->references('id')->on('wikis')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        Schema::table('wiki_entity_imports', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });

        Schema::table('wiki_lifecycle_events', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });

        Schema::table('wiki_notification_sent_records', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
        });

        Schema::table('wiki_profiles', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });

        Schema::table('wiki_site_stats', function (Blueprint $table) {
            $table->unsignedInteger('wiki_id')->change();
            $table->foreign('wiki_id')->references('id')->on('wikis');
        });
    }
};
