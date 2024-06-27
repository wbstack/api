<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Wiki;

class WikiEntityImportJob implements ShouldQueue
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
        $wiki = Wiki::findOrFail($this->wikiId);
        $creds = $this->acquireCredentials($wiki->domain);
    }

    private static function acquireCredentials(string $wikiDomain): OAuthCredentials
    {
        return new OAuthCredentials(
            consumerToken: '?',
            consumerSecret: '?',
            accessToken: '?',
            accessSecret: '?',
        );
    }
}

class OAuthCredentials
{
    public function __construct(
        public string $consumerToken,
        public string $consumerSecret,
        public string $accessToken,
        public string $accessSecret,
    )
    {}

    public function marshalEnv(string $prefix = 'TARGET_WIKI'): array
    {
        return [
            $prefix.'_CONSUMER_TOKEN' => $this->consumerToken,
            $prefix.'_CONSUMER_SECRET' => $this->consumerSecret,
            $prefix.'_ACCESS_TOKEN' => $this->accessToken,
            $prefix.'_ACCESS_SECRET' => $this->accessSecret,
        ];
    }
}
