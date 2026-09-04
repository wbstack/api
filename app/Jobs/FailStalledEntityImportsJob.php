<?php

namespace App\Jobs;

use App\WikiEntityImport;
use App\WikiEntityImportStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FailStalledEntityImportsJob implements ShouldQueue {
    use Queueable;

    public function handle(): void {
        $deadline = Carbon::now()->subHours(24);
        $now = Carbon::now();

        $stalledImports = WikiEntityImport::where([
            ['status', '=', WikiEntityImportStatus::Pending],
            ['started_at',  '<=', $deadline],
        ]);
        $stalledImports->update([
            'status' => WikiEntityImportStatus::Failed,
            'finished_at' => $now,
        ]);

        if ($stalledImports->count() > 0) {
            Log::info(
                'Marked ' . $stalledImports->count() . ' WikiEntityImports as failed as they seem to be stalled.'
            );
        }
    }
}
