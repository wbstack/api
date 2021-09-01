<?php

namespace App\Jobs;

use Maclof\Kubernetes\Client;
use App\Wiki;

/**
 * Job for deleting a wikis kubernetes ingress
 */
class KubernetesIngressDeleteJob extends Job
{
    private $id;
    private $wikiDomain;

    /**
     * @param int|string $wikiId
     */
    public function __construct( $wikiId )
    {
        $this->id = $wikiId;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $wiki = Wiki::withTrashed()->where('id', $this->id )->first();

        if ( !$wiki) {
            $this->fail( new \RuntimeException("Could not find wiki with id {$this->id}") );
            return;
        }

        if ( !$wiki->deleted_at) {
            $this->fail( new \RuntimeException("Wiki {$this->id} is not deleted, but it's ingress got called to be deleted.") );
            return;
        }

        $client = new Client([
            'master' => 'https://kubernetes.default.svc',
            'ca_cert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
            'token'   => '/var/run/secrets/kubernetes.io/serviceaccount/token',
        ]);

        $exists = $client->ingresses()->exists( KubernetesIngressCreate::getKubernetesIngressName($this->id) );
        
        // just exit if there is nothing no need to fail
        if ( !$exists ) {
            return;
        }

        // TODO how to test any of this?
        $result = $client->ingresses()->deleteByName( KubernetesIngressCreate::getKubernetesIngressName($this->id) );

    }
}
