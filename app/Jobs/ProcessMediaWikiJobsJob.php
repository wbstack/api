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
    private const API_JOB_CONCURRENCY_LIMIT = intval(getenv('API_JOB_CONCURRENCY_LIMIT') || '8', 10);

    public function __construct (string $wikiDomain)
    {
        $this->wikiDomain = $wikiDomain;
    }

    public function uniqueId(): string
    {
        return $this->wikiDomain;
    }

    public function handle (Client $kubernetesClient): void
    {
        $kubernetesClient->setNamespace('api-jobs');

        $numJobs = $kubernetesClient->jobs()->setFieldSelector([
            'status.phase' => 'Running',
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'run-all-mw-jobs'
        ])->find()->count();

        if ($numJobs >= self::API_JOB_CONCURRENCY_LIMIT) {
            Log::info(
                $numJobs.' running jobs were found, skipping creation of new ones in order not to exceed the given concurrency limit of '.self::API_JOB_CONCURRENCY_LIMIT.'.'
            );
            return;
        }

        $hasRunningJob = $kubernetesClient->jobs()->setFieldSelector([
            'status.phase' => 'Running'
        ])->setLabelSelector([
            'app.kubernetes.io/instance' => $this->wikiDomain
        ])->find()->isNotEmpty();

        if ($hasRunningJob) {
            Log::info(
                'Job for wiki "'.$this->wikiDomain.'" is still in process, skipping creation.'
            );
            return;
        }

        $mwPod = $kubernetesClient->pods()->setFieldSelector([
            'status.phase' => 'Running'
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'mediawiki',
            'app.kubernetes.io/component' => 'app-backend'
        ])->first();

        if ($mwPod === null) {
            $this->fail(
                new \RuntimeException(
                    'Unable to find a running MediaWiki pod in the cluster, '.
                    'cannot continue.'
                )
            );
            return;
        }

        $mwPod = $mwPod->toArray();

        $jobSpec = new KubernetesJob([
            'metadata' => [
                'generateName' => 'run-all-mw-jobs-',
                'namespace' => 'api-jobs',
                'labels' => [
                    'app.kubernetes.io/instance' => $this->wikiDomain,
                    'app.kubernetes.io/name' => 'run-all-mw-jobs'
                ]
            ],
            'spec' => [
                'ttlSecondsAfterFinished' => 60 * 60,
                'template' => [
                    'metadata' => [
                        'name' => 'run-all-mw-jobs'
                    ],
                    'spec' => [
                        'containers' => [
                            0 => [
                                'name' => 'run-all-mw-jobs',
                                'image' => $mwPod['spec']['containers'][0]['image'],
                                'env' => array_merge(
                                    $mwPod['spec']['containers'][0]['env'],
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
        $jobName = data_get($job, 'metadata.name', 'unknown');
        Log::info(
            'MediaWiki Job for wiki "'.$this->wikiDomain.'" created with name "'.$jobName.'".'
        );
        return;
    }

}
