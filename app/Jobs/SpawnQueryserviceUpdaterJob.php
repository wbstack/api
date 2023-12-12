<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job as KubernetesJob;

class SpawnQueryserviceUpdaterJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable;

    public string $wikiDomain;
    public string $entities;
    public string $sparqlUrl;
    public string $qsKubernetesNamespace;

    public function __construct (string $wikiDomain, string $entities, string $sparqlUrl)
    {
        $sortedEntities = explode(',', $entities);
        asort($sortedEntities);

        $this->wikiDomain = $wikiDomain;
        $this->entities = implode(',', $sortedEntities);
        $this->sparqlUrl = $sparqlUrl;
        $this->qsKubernetesNamespace = Config::get('wbstack.qs_job_namespace');
    }

    public function uniqueId(): string
    {
        return $this->wikiDomain.$this->entities;
    }

    public function handle (Client $kubernetesClient): void
    {
        $kubernetesClient->setNamespace('default');
        $qsUpdaterPod = $kubernetesClient->pods()->setFieldSelector([
            'status.phase' => 'Running'
        ])->setLabelSelector([
            'app.kubernetes.io/name' => 'queryservice-updater',
        ])->first();

        if ($qsUpdaterPod === null) {
            $this->fail(
                new \RuntimeException(
                    'Unable to find a running queryservice-updater pod in the cluster, '.
                    'cannot continue.'
                )
            );
            return;
        }
        $qsUpdaterPod = $qsUpdaterPod->toArray();

        $kubernetesClient->setNamespace($this->qsKubernetesNamespace);
        $jobSpec = new KubernetesJob([
            'metadata' => [
                'name' => 'run-qs-updater-'.hash('sha1', $this->uniqueId()),
                'namespace' => $this->qsKubernetesNamespace,
                'labels' => [
                    'app.kubernetes.io/instance' => $this->wikiDomain,
                    'app.kubernetes.io/name' => 'run-qs-updater',
                    'entityPayload' => $this->entities,
                ]
            ],
            'spec' => [
                'ttlSecondsAfterFinished' => 14 * 24 * 60 * 60, // 2 weeks
                'template' => [
                    'metadata' => [
                        'name' => 'run-qs-updater'
                    ],
                    'spec' => [
                        'containers' => [
                            0 => [
                                'name' => 'run-qs-updater',
                                'image' => $qsUpdaterPod['spec']['containers'][0]['image'],
                                'env' => $qsUpdaterPod['spec']['containers'][0]['env'],
                                'command' => [
                                    0 => 'bash',
                                    1 => '-c',
                                    2 => <<<CMD
                                    /wdqsup/runUpdateWbStack.sh -- \
                                        --wikibaseHost {$this->wikiDomain} \
                                        --ids {$this->entities} \
                                        --entityNamespaces 120,122,146 \
                                        --sparqlUrl {$this->sparqlUrl} \
                                        --wikibaseScheme http \
                                        --conceptUri https://{$this->wikiDomain}
                                    CMD
                                ],
                            ]
                        ],
                        'restartPolicy' => 'Never'
                    ]
                ]
            ]
        ]);

        $job = $kubernetesClient->jobs()->apply($jobSpec);
        $jobName = data_get($job, 'metadata.name');
        if (!$jobName) {
            // The k8s client does not fail reliably on 4xx responses, so checking the name
            // currently serves as poor man's error handling.
            $this->fail(
                new \RuntimeException('Queryservice Updater creation for wiki "'.$this->wikiDomain.'" failed.')
            );
            return;
        }
        Log::info(
            'Queryservice Updater for wiki "'.$this->wikiDomain.'" and entities "'.$this->entities.'" exists or was created with name "'.$jobName.'".'
        );

        return;
    }

}
