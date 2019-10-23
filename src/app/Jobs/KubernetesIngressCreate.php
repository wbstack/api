<?php

namespace App\Jobs;

use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Ingress;

class KubernetesIngressCreate extends Job
{

    private $id;
    private $wikiDomain;
    /**
     * @return void
     */
    public function __construct($id, $wikiDomain)
    {
        $this->id = $id;
        $this->wikiDomain = $wikiDomain;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Docs for the client https://github.com/maclof/kubernetes-client

        // Connection example from: https://github.com/maclof/kubernetes-client#using-a-service-account
        echo "Creating k8s client";
        $client = new Client([
            // Service host per: https://kubernetes.io/docs/tasks/administer-cluster/access-cluster-api/#accessing-the-api-from-a-pod
            'master' => 'https://kubernetes.default.svc',
            // TODO maybe these files should only exist on the job runner?
            'ca_cert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
            'token'   => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ]);

        $ingress = new Ingress([
            'metadata' => [
                /**
                 * From: https://kubernetes.io/docs/concepts/overview/working-with-objects/names/
                 * Kubernetes resources can have names up to 253 characters long.
                 * The characters allowed in names are: digits (0-9), lower case letters (a-z), -, and ..
                 *
                 * So, just use the ID number here
                 * We can add a label showing the domain and allowing selection.
                 */
                'name' => 'mediawiki-site-' . $this->id,
                'namespace' => 'default',
                'labels' => [
                    'id' => strval($this->id),
                    'domain' => $this->wikiDomain,
                    // Generation should be updated when this ingress spec is changed.
                    // This will allow updating older ingresses to match newer ones etc.
                    'generation' => '2019-10-23.1',
                    'app.kubernetes.io/managed-by' => 'wbstack-platform',
                ],
            ],
            'spec' => [
                'rules' => [
                    [
                        'host' => $this->wikiDomain,
                        'http' => [
                            'paths' => [
                                [
                                    'path' => '/',
                                    'backend' => [
                                        'serviceName' => 'mediawiki',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/query',
                                    'backend' => [
                                        'serviceName' => 'queryservice-ui',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/query/sparql',
                                    'backend' => [
                                        'serviceName' => 'queryservice-proxy',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/tools/quickstatements',
                                    'backend' => [
                                        'serviceName' => 'quickstatements',
                                        'servicePort' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        echo "Getting ingress resources";
        $ingresses = $client->ingresses();
        echo "Checking if resource exists: " . $ingress->getMetadata('name');
        $exists = $ingresses->exists($ingress->getMetadata('name'));
        if ($exists) {
            die('Should not already exist...');
        }

        echo "Creating ingress resource";
        $result = $client->ingresses()->create($ingress);
        // TODO check result
        // TODO output something?
    }
}
