<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            // TODO: or should this column name just be `type`?
            $table->enum('policy_type', ['terms-of-use', 'hosting-policy']);
            $table->date('active_from')->nullable()->default(null);
            // TODO: or `content_reference`?
            $table->string('content_vue_file', 255);

            // Use Eloquent built in to create nullable `created_at` and `updated_at`
            // timestamp fields
            $table->timestamps();

            // TODO: won't be able to create two upcoming policies of the same type with `active_from` set to `null`,
            // but that seems like a reasonable restriction
            $table->unique(['policy_type', 'active_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('policies');
    }
};
