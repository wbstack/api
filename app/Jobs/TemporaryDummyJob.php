<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TemporaryDummyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($domain, $entites, $sparqlUrl)
    {
        Log::info('Dummy Job received domain: '.$domain);
        Log::info('Dummy Job received entities: '.$entites);
        Log::info('Dummy Job received sparqlUrl: '.$sparqlUrl);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Dummy job ran, but did nothing.');
    }
}
