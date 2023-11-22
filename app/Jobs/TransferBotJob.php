<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job as KubernetesJob;

class ProcessMediaWikiJobsJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable;

    private string $wikiDomain;
    private string $jobsKubernetesNamespace;
    private string $targetWikiDomain;
    private string $sourceWikiDomain;

    // $pages can be omitted and we can get everything using -start
    // or we can pass a list of pages with
    public function __construct (string $targetWikiDomain, string $sourceWikiDomain, array $pages = null)
    {
        $this->targetWikiDomain = $targetWikiDomain;
        $this->sourcewikiDomain = $sourceWikiDomain;
        $this->pages = $pages;

        $this->jobsKubernetesNamespace = env('TRANSFERBOT_JOB_NAMESPACE', 'api-jobs');
    }

    public function uniqueId(): string
    {
        return $this->targetWikiDomain;
    }

    public function handle (Client $kubernetesClient): void
    {
        $kubernetesClient->setNamespace($this->jobsKubernetesNamespace);
        $jobSpec = new KubernetesJob([
            
        ]);

        $job = $kubernetesClient->jobs()->create($jobSpec);
        $jobName = data_get($job, 'metadata.name');
        if (!$jobName) {
            // The k8s client does not fail reliably on 4xx responses, so checking the name
            // currently serves as poor man's error handling.
            $this->fail(
                new \RuntimeException('Job creation for wiki "'.$this->targetWikiDomain.'" failed.')
            );
            return;
        }
        Log::info(
            'TransferBot Job for wiki "'.$this->targetWikiDomain.'" exists or was created with name "'.$jobName.'".'
        );

        return;
    }

}
