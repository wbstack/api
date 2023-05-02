<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job as KubernetesJob;

class ProcessMediaWikiJobsJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable;

    private $wikiDomain: string;

    public function __construct ( string $wikiDomain )
    {
        $this->wikiDomain = $wikiDomain;
    }

    public function uniqueId(): string
    {
        return $this->wikiDomain;
    }

    public function handle (Client $kubernetesClient): void
    {
        $mwPod = $kubernetesClient->pods()->setFieldSelector([
            'status.phase' => 'Running'
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'mediawiki',
            'app.kubernetes.io/component' => 'app-backend'
        ])->first();

        if (!$mwPod) {
            throw new Exception(
                'Unable to find a running MediaWiki pod in the cluster, '.
                'cannot continue.'
            );
        }

        $job = new KubernetesJob([]);
        $kubernetesClient->jobs->create($job);
        return;
    }

}
