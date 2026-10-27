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
        Schema::table('wiki_suspension_actions', function (Blueprint $table) {
            $table->dropColumn('suspension_type');

            // Removing the indefinitely_allowed enum option requires specifying the list of allowed options again
            $table
                ->enum('reason', [
                    'manual_expiry',
                    'automatic_expiry',
                    'tou_violation',
                    'hp_violation',
                ])
                ->change();
        });

        Schema::rename('wiki_suspension_actions', 'wiki_suspensions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wiki_suspensions', function (Blueprint $table) {
            $table->enum('suspension_type', ['suspend', 'unsuspend'])->after('id');

            // Adding the indefinitely_allowed enum option requires specifying the list of all allowed options
            $table
                ->enum('reason', [
                    'manual_expiry',
                    'automatic_expiry',
                    'tou_violation',
                    'hp_violation',
                    'indefinitely_allowed',
                ])
                ->change();
        });

        Schema::rename('wiki_suspensions', 'wiki_suspension_actions');
    }
};
