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
                'generateName' => 'run-all-mw-jobs-'
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
        $jobName = $kubernetesClient->jobs()->create($jobSpec)['metadata']['name'];

        // TODO: figure out how to use `wait` method instead
        while (true) {
            $job = $kubernetesClient->jobs()->setFieldSelector([
                'metadata.name' => $jobName
            ])->first()->toArray();

            if (array_key_exists('completionTime', $job['status'])) {
                if (!$job['status']['succeeded']) {
                    $this->fail(
                        new \RuntimeException('Failed to run Kubernetes job '.$jobName)
                    );
                }
                break;
            }

            sleep(1);
        }
        return;
    }

}
