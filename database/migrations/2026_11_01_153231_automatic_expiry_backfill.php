<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = CarbonImmutable::now();
        // Create an automatic expiry suspension for all Wikis that don't currently have one
        DB::table('wikis')
            ->leftJoin('wiki_suspensions', 'wikis.id', '=', 'wiki_suspensions.wiki_id')
            ->whereNull('wiki_id')
            ->orderBy('wikis.id')
            ->select('wikis.id', 'wikis.created_at')
            ->chunkById(100, fn ($wikis) => DB::table('policy_acceptances')->insert(
                $wikis->map(fn ($wiki) => [
                    'user_id' => $wiki->id,
                    'accepted_at' => $wiki->created_at,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all()
            ),
                'users.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException("The 'down' method is not supported");
    }
};
