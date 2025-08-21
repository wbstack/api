<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::statement('
                ALTER TABLE `wiki_profiles` CHANGE COLUMN `audience` `audience` 
                ENUM("narrow", "wide", "other")
            ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        //  n.b. this rollback won't work if there are null audience values in the db
        DB::statement('
                ALTER TABLE `wiki_profiles` CHANGE COLUMN `audience` `audience`
                ENUM("narrow", "wide", "other")
                NOT NULL
            ');

    }
};
