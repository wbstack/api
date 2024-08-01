<?php

use App\Jobs\UpdateWikiSiteStatsJob;
use App\WikiLifecycleEvents;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        WikiLifecycleEvents::all()->map->delete();
        (new UpdateWikiSiteStatsJob())->handle();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
