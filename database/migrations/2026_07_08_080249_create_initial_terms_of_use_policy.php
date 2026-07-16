<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    private const TABLE_NAME = 'policies';

    private const POLICY_TYPE = 'terms-of-use';

    private const ACTIVE_FROM = '2022-01-01';

    /**
     * Run the migrations.
     */
    public function up(): void {
        $now = now();
        DB::table(self::TABLE_NAME)->insert([
            'policy_type' => self::POLICY_TYPE,
            'active_from' => self::ACTIVE_FROM,
            'content_vue_file' => 'terms-of-use/version-1.vue',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::table(self::TABLE_NAME)->where([
            'policy_type' => self::POLICY_TYPE,
            'active_from' => self::ACTIVE_FROM,
        ])->delete();
    }
};
