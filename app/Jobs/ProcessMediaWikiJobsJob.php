<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job as KubernetesJob;

class ProcessMediaWikiJobsJob implements ShouldBeUnique, ShouldQueue {
    use InteractsWithQueue, Queueable;

    private string $wikiDomain;

    private string $jobsKubernetesNamespace;

    public function __construct(string $wikiDomain) {
        $this->wikiDomain = $wikiDomain;
        $this->jobsKubernetesNamespace = Config::get('wbstack.api_job_namespace');
    }

    public function uniqueId(): string {
        return $this->wikiDomain;
    }

    public function handle(Client $kubernetesClient): void {
        $kubernetesClient->setNamespace('default');
        $mediawikiPod = $kubernetesClient->pods()->setFieldSelector([
            'status.phase' => 'Running',
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'mediawiki',
            'app.kubernetes.io/component' => 'app-backend',
        ])->first();

        if ($mediawikiPod === null) {
            $this->fail(
                new \RuntimeException(
                    'Unable to find a running MediaWiki pod in the cluster, ' .
                    'cannot continue.'
                )
            );

            return;
        }
        $mediawikiPod = $mediawikiPod->toArray();

        $kubernetesClient->setNamespace($this->jobsKubernetesNamespace);
        $jobSpec = new KubernetesJob([
            'metadata' => [
                'name' => 'run-all-mw-jobs-' . hash('sha1', $this->wikiDomain),
                'namespace' => $this->jobsKubernetesNamespace,
                'labels' => [
                    'app.kubernetes.io/instance' => $this->wikiDomain,
                    'app.kubernetes.io/name' => 'run-all-mw-jobs',
                ],
            ],
            'spec' => [
                'ttlSecondsAfterFinished' => 0,
                'template' => [
                    'metadata' => [
                        'name' => 'run-all-mw-jobs',
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
                            ],
                        ],
                        'restartPolicy' => 'Never',
                    ],
                ],
            ],
        ]);

        $job = $kubernetesClient->jobs()->apply($jobSpec);
        $jobName = data_get($job, 'metadata.name');
        if (data_get($job, 'status') === 'Failure' || !$jobName) {
            // The k8s client does not fail reliably on 4xx responses, so checking the name
            // currently serves as poor man's error handling.
            $this->fail(
                new \RuntimeException('Job creation for wiki "' . $this->wikiDomain . '" failed with message: ' . data_get($job, 'message', 'n/a'))
            );

            return;
        }
        Log::info(
            'MediaWiki Job for wiki "' . $this->wikiDomain . '" exists or was created with name "' . $jobName . '".'
        );

    }
}
