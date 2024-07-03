<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WikiEntityImportDummyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $wikiId,
        public string $sourceWikiUrl,
        public array $entityIds,
        public string $importId,
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Handling job for ".$this->wikiId.", ".$this->sourceWikiUrl.", ".implode(",", $this->entityIds)." ,".$this->importId);
    }
}
