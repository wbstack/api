<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        $policyId = $this->getPolicyId();
        $timestamp = now();

        DB::table('users')
            ->leftJoin('policy_acceptances', fn ($join) => $join->on('users.id', '=', 'user_id')
                ->where('policy_id', '=', $policyId)
            )
            ->whereNull('policy_id')
            ->orderBy('users.id')
            ->select('users.id', 'users.created_at')
            ->chunkById(100, fn ($users) => DB::table('policy_acceptances')->insert(
                $users->map(fn ($user) => [
                    'user_id' => $user->id,
                    'policy_id' => $policyId,
                    'accepted_at' => $user->created_at,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all()
            ),
                'users.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::table('policy_acceptances')
            ->where('policy_id', $this->getPolicyId())
            ->whereColumn('created_at', '>', 'accepted_at')
            ->delete();
    }

    /**
     * @return int The policy ID of our existing terms of use.
     */
    private function getPolicyId(): int {
        return DB::table('policies')
            ->where('policy_type', 'terms-of-use')
            ->where('active_from', '2022-01-01')
            ->soleValue('id');
    }
};
