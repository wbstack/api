<?php

use App\Jobs\UpdateWikiSiteStatsJob;
use App\WikiLifecycleEvents;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        WikiLifecycleEvents::query()->delete();
        UpdateWikiSiteStatsJob::dispatch();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        //
    }
};
