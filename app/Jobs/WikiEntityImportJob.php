<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
        $response = Http::withHeaders(['host' => $wikiDomain])->asForm()->post(
            getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackPlatformOauthGet&format=json',
            [
                'consumerName' => 'WikiEntityImportJob',
                'ownerOnly' => '1',
                'consumerVersion' => '1',
                'grants' => 'createeditmovepage|highvolume',
                'callbackUrlTail' => '/w/index.php',
            ],
        );

        if ($response->status() > 399) {
            throw new \Exception('Unexpected status code '.$response->status().' from Mediawiki');
        }

        $body = $response->json();
        if (!$body || $body['wbstackPlatformOauthGet']['success'] !== '1') {
            throw new \ErrorException('Unexpected error acquiring oauth credentials for wiki '.$wikiDomain);
        }

        return OAuthCredentials::unmarshalMediaWikiResponse($body);
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

    public static function unmarshalMediaWikiResponse(array $response): OAuthCredentials
    {
        $data = $response['wbstackPlatformOauthGet']['data'];
        return new OAuthCredentials(
            consumerToken: $data['consumerKey'],
            consumerSecret: $data['consumerSecret'],
            accessToken: $data['accessKey'],
            accessSecret: $data['accessSecret'],
        );
    }

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
