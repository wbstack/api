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
        // DROP EXISTING FOREIGN KEY CONSTRAINTS
        Schema::table('policy_acceptances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // UPDATE PRIMARY KEY COLUMN TYPE
        Schema::table('users', function (Blueprint $table) {
            // `->id()` is an alias for `->bigIncrements('id')`
            // https://laravel.com/framework/docs/11.x/migrations#column-method-id
            $table->id()->change();
        });

        // UPDATE FOREIGN KEY COLUMN TYPE AND RECREATE DROPPED CONSTRAINTS
        Schema::table('policy_acceptances', function (Blueprint $table) {
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
        //  DROP EXISTING FOREIGN KEY CONSTRAINTS
        Schema::table('policy_acceptances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // REVERSE PRIMARY KEY COLUMN TYPE CHANGE
        Schema::table('users', function (Blueprint $table) {
            $table->increments('id')->change();
        });

        // REVERSE FOREIGN KEY COLUMN TYPE CHANGE AND RECREATE DROPPED CONSTRAINTS
        Schema::table('policy_acceptances', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }
};
