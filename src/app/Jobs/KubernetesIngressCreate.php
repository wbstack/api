<?php

namespace App\Jobs;

use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Ingress;

/**
 * This can be run with for example:
 * php artisan job:handle KubernetesIngressCreate 999999999,wiki.addshore.com ,
 *
 * If you need to cleanup a test run of this you need to remove the ingress and the related secret
 */
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
        echo 'Creating k8s client' . PHP_EOL;
        $client = new Client([
            // Service host per: https://kubernetes.io/docs/tasks/administer-cluster/access-cluster-api/#accessing-the-api-from-a-pod
            'master' => 'https://kubernetes.default.svc',
            // TODO maybe these files should only exist on the job runner?
            'ca_cert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
            'token'   => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ]);

        $ingress = new Ingress([
            'metadata' => [
                /*
                 * From: https://kubernetes.io/docs/concepts/overview/working-with-objects/names/
                 * Kubernetes resources can have names up to 253 characters long.
                 * The characters allowed in names are: digits (0-9), lower case letters (a-z), -, and ..
                 *
                 * So, just use the ID number here
                 * We can add a label showing the domain and allowing selection.
                 */
                'name' => 'mediawiki-site-'.$this->id,
                'namespace' => 'default',
                'labels' => [
                    'wbstack-wiki-id' => strval($this->id),
                    'wbstack-wiki-domain' => $this->wikiDomain,
                    // Generation should be updated when this ingress spec is changed.
                    // This will allow updating older ingresses to match newer ones etc.
                    'wbstack-ingress-generation' => '2020-04-10.1',
                    'app.kubernetes.io/managed-by' => 'wbstack-platform',
                ],
                'annotations' => [
                    'kubernetes.io/ingress.class' => 'nginx',
                    'nginx.ingress.kubernetes.io/force-ssl-redirect' => 'true',
                    'nginx.ingress.kubernetes.io/use-regex' => 'true',
                    'nginx.ingress.kubernetes.io/rewrite-target' => '/$2',
                    'nginx.ingress.kubernetes.io/configuration-snippet' => 'rewrite ^(/query|/tools/quickstatements)$ $1/ permanent;',
                    'cert-manager.io/cluster-issuer' => 'letsencrypt-staging'
                ],
            ],
            'spec' => [
                'tls' => [
                    [
                        'hosts' => [
                            $this->wikiDomain,
                        ],
                        'secretName' => 'mediawiki-site-tls-' . $this->id
                    ],
                ],
                'rules' => [
                    [
                        'host' => $this->wikiDomain,
                        'http' => [
                            'paths' => [
                                [
                                    'path' => '/()(.*)',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'mediawiki-app-web',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/()(w/api.php.*)',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'mediawiki-app-web-api',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/(query)(.*)',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'queryservice-ui',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/(query/)(sparql.*)',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'queryservice-proxy',
                                        'servicePort' => 80,
                                    ],
                                ],
                                [
                                    'path' => '/(tools/quickstatements)(.*)',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'tool-quickstatements',
                                        'servicePort' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        echo 'Getting ingress resources' . PHP_EOL;
        $ingresses = $client->ingresses();
        echo 'Checking if resource exists: '.$ingress->getMetadata('name') . PHP_EOL;
        $exists = $ingresses->exists($ingress->getMetadata('name'));
        if ($exists) {
            die('Should not already exist...');
        }

        echo 'Creating ingress resource' . PHP_EOL;
        $result = $client->ingresses()->create($ingress);
        // TODO check result
        // TODO output something?
    }
}
