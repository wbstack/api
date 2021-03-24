<?php

namespace App\Jobs;

use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Ingress;
use Http\Adapter\Guzzle6\Client as Guzzle6Client;

/**
 * This can be run with for example:
 * php artisan wbs-job:handle KubernetesIngressCreate 999999999,wiki.addshore.com ,
 *
 * If you need to cleanup a test run of this you need to remove the ingress and the related secret
 */
class KubernetesIngressCreate extends Job
{
    private $id;
    private $wikiDomain;

    /**
     * @param int|string $wikiId
     * @param string $wikiDomain
     */
    public function __construct($wikiId, $wikiDomain)
    {
        $this->id = $wikiId;
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
        $httpClient = Guzzle6Client::createWithConfig([
            'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
        ]);
        $client = new Client([
            // Service host per: https://kubernetes.io/docs/tasks/administer-cluster/access-cluster-api/#accessing-the-api-from-a-pod
            'master' => 'https://kubernetes.default.svc',
            'token'   => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ], null, $httpClient);

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
                    'wbstack-ingress-generation' => '2020-04-18.1',
                    'app.kubernetes.io/managed-by' => 'wbstack-platform',
                ],
                'annotations' => [
                    'kubernetes.io/ingress.class' => 'nginx',
                    'nginx.ingress.kubernetes.io/force-ssl-redirect' => 'true',
                    'cert-manager.io/cluster-issuer' => 'letsencrypt-prod'
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
                                    'path' => '/',
                                    'backend' => [
                                        // TODO this should be an env var...
                                        'serviceName' => 'platform-nginx',
                                        'servicePort' => 8080,
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
            $this->fail(
                new \RuntimeException( 'Ingress already exists, but it should not' )
            );
            return;//safegaurd
        }

        echo 'Creating ingress resource' . PHP_EOL;
        $result = $client->ingresses()->create($ingress);
        // TODO check result
        // TODO output something?
    }
}
