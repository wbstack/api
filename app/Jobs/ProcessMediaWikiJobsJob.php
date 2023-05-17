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

    public function __construct (string $wikiDomain)
    {
        $this->wikiDomain = $wikiDomain;
        $this->jobsKubernetesNamespace = env('API_JOB_NAMESPACE', 'api-jobs');
    }

    public function uniqueId(): string
    {
        return $this->wikiDomain;
    }

    public function handle (Client $kubernetesClient): void
    {
        $kubernetesClient->setNamespace($this->jobsKubernetesNamespace);

        // Room for optimization: whenever we move away from maclof/kubernetes, we could also
        // handle deduplication per wiki on Kubernetes level: When trying to create a job that
        // already exists, Kubernetes returns "409 Conflict" which we could consider
        // wanted behavior and just try to create a job every time this Job is called.
        //
        // This is currently not possible because the Kubernetes client does not let us inspect
        // the response code of a failed job creation.
        $hasRunningJobForSameWiki = $kubernetesClient->jobs()->setLabelSelector([
            'app.kubernetes.io/instance' => $this->wikiDomain
        ])->find()->isNotEmpty();

        if ($hasRunningJobForSameWiki) {
            Log::info(
                'Job for wiki "'.$this->wikiDomain.'" is still in process, skipping creation.'
            );
            return;
        }

        $kubernetesClient->setNamespace('default');

        $mediawikiPod = $kubernetesClient->pods()->setFieldSelector([
            'status.phase' => 'Running'
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'mediawiki',
            'app.kubernetes.io/component' => 'app-backend'
        ])->first();

        if ($mediawikiPod === null) {
            $this->fail(
                new \RuntimeException(
                    'Unable to find a running MediaWiki pod in the cluster, '.
                    'cannot continue.'
                )
            );
            return;
        }

        $mediawikiPod = $mediawikiPod->toArray();

        $kubernetesClient->setNamespace($this->jobsKubernetesNamespace);
        $jobSpec = new KubernetesJob([
            'metadata' => [
                'generateName' => 'run-all-mw-jobs-',
                'namespace' => $this->jobsKubernetesNamespace,
                'labels' => [
                    'app.kubernetes.io/instance' => $this->wikiDomain,
                    'app.kubernetes.io/name' => 'run-all-mw-jobs'
                ]
            ],
            'spec' => [
                'ttlSecondsAfterFinished' => 0,
                'template' => [
                    'metadata' => [
                        'name' => 'run-all-mw-jobs'
                    ],
                    'spec' => [
                        'containers' => [
                            0 => [
                                'name' => 'run-all-mw-jobs',
                                'image' => $mediawikiPod['spec']['containers'][0]['image'],
                                'env' => array_merge(
                                    $mediawikiPod['spec']['containers'][0]['env'],
                                    [['name' => 'WBS_DOMAIN', 'value' => $this->wikiDomain]]
                                ),
                                'command' => [
                                    0 => 'bash',
                                    1 => '-c',
                                    2 => <<<'CMD'
                                    JOBS_TO_GO=1
                                    while [ "$JOBS_TO_GO" != "0" ]
                                    do
                                        echo "Running 1000 jobs"
                                        php w/maintenance/runJobs.php --maxjobs 1000
                                        echo Waiting for 1 seconds...
                                        sleep 1
                                        JOBS_TO_GO=$(php w/maintenance/showJobs.php | tr -d '[:space:]')
                                        echo $JOBS_TO_GO jobs to go
                                    done
                                    CMD
                                ],
                            ]
                        ],
                        'restartPolicy' => 'Never'
                    ]
                ]
            ]
        ]);

        $job = $kubernetesClient->jobs()->create($jobSpec);
        $jobName = data_get($job, 'metadata.name', 'n/a');
        Log::info(
            'MediaWiki Job for wiki "'.$this->wikiDomain.'" created with name "'.$jobName.'".'
        );

        return;
    }

}
