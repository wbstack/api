<?php

use App\Policy;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    private const POLICY_TYPE = 'terms-of-use';
    private const ACTIVE_FROM = '2022-01-01';

    /**
     * Run the migrations.
     */
    public function up(): void {
        Policy::create([
            'policy_type' => self::POLICY_TYPE,
            'active_from' => self::ACTIVE_FROM,
            'content_vue_file' => 'terms-of-use/version-1.vue',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Policy::where([
            'policy_type' => self::POLICY_TYPE,
            'active_from' => self::ACTIVE_FROM,
        ])->delete();
    }
};
