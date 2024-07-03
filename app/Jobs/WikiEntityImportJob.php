<?php

namespace App\Jobs;

use App\WikiEntityImportStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Wiki;
use App\WikiEntityImport;
use Carbon\Carbon;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job as KubernetesJob;

class WikiEntityImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $wikiId,
        public string $sourceWikiUrl,
        public array $entityIds,
        public int $importId,
    )
    {}

    public string $targetWikiUrl;

    /**
     * Execute the job.
     */
    public function handle(Client $kubernetesClient): void
    {
        $import = null;
        try {
            $wiki = Wiki::findOrFail($this->wikiId);
            $import = WikiEntityImport::findOrFail($this->importId);
            $creds = $this->acquireCredentials($wiki->domain);

            $this->targetWikiUrl = str_contains($wiki->domain, "localhost")
                ? "http://".$wiki->domain
                : "https://".$wiki->domain;

            $kubernetesJob = new TransferBotKubernetesJob(
                kubernetesClient: $kubernetesClient,
                wiki: $wiki,
                creds: $creds,
                entityIds: $this->entityIds,
                sourceWikiUrl: $this->sourceWikiUrl,
                targetWikiUrl: $this->targetWikiUrl,
            );
            $jobName = $kubernetesJob->spawn();
            Log::info(
                'transferbot job for wiki "'.$wiki->domain.'" exists or was created with name "'.$jobName.'".'
            );
        } catch (\Exception $ex) {
            Log::error('Entity import job failed with error: '.$ex->getMessage());
            $import?->update([
                'status' => WikiEntityImportStatus::Failed,
                'finished_at' => Carbon::now()
            ]);
            $this->fail(
                new \Exception('Error spawning transferbot for wiki '.$wiki->domain.': '.$ex->getMessage()),
            );
        }
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

class TransferBotKubernetesJob
{
    public function __construct(
        public Client $kubernetesClient,
        public Wiki $wiki,
        public OAuthCredentials $creds,
        public array $entityIds,
        public string $sourceWikiUrl,
        public string $targetWikiUrl,
        public string $kubernetesNamespace = 'api-jobs',
    ){}

    public function spawn(): string
    {
        $spec = $this->constructSpec();
        $jobSpec = new KubernetesJob($spec);

        $jobObject = $this->kubernetesClient->jobs()->apply($jobSpec);
        $jobName = data_get($jobObject, 'metadata.name');
        if (data_get($jobObject, 'status') === 'Failure' || !$jobName) {
            // The k8s client does not fail reliably on 4xx responses, so checking the name
            // currently serves as poor man's error handling.
            throw new \RuntimeException(
                'transferbot creation for wiki "'.$this->wiki->domain.'" failed with message: '.data_get($jobObject, 'message', 'n/a')
            );
        }
        return $jobName;
    }

    private function constructSpec(): array
    {
        return [
            'metadata' => [
                'name' => 'run-transferbot-'.hash('sha1', $this->wiki->id),
                'namespace' => $this->kubernetesNamespace,
                'labels' => [
                    'app.kubernetes.io/instance' => $this->wiki->domain,
                    'app.kubernetes.io/name' => 'run-transferbot',
                ]
            ],
            'spec' => [
                'ttlSecondsAfterFinished' => 24 * 60 * 60, // 1 day
                'template' => [
                    'metadata' => [
                        'name' => 'run-transferbot'
                    ],
                    'spec' => [
                        'containers' => [
                            0 => [
                                'name' => 'run-qs-updater',
                                'image' => 'ghcr.io/wbstack/transferbot:latest',
                                'env' => $this->creds->marshalEnv(),
                                'command' => $this->sourceWikiUrl.' '.$this->targetWikiUrl.' '.implode(" ", $this->entityIds),
                            ]
                        ],
                        'restartPolicy' => 'Never'
                    ]
                ]
            ]
        ];
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
