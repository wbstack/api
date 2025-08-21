<?php

namespace App\Jobs;

use App\Wiki;
use Maclof\Kubernetes\Client;

/**
 * Job for deleting a wikis kubernetes ingress
 */
class KubernetesIngressDeleteJob extends Job {
    private $id;

    private $wikiDomain;

    /**
     * @param  int|string  $wikiId
     */
    public function __construct($wikiId) {
        $this->id = $wikiId;
    }

    /**
     * @return void
     */
    public function handle(Client $client) {
        $wiki = Wiki::withTrashed()->where('id', $this->id)->first();

        if (! $wiki) {
            $this->fail(new \RuntimeException("Could not find wiki with id {$this->id}"));

            return;
        }

        if (! $wiki->deleted_at) {
            $this->fail(new \RuntimeException("Wiki {$this->id} is not deleted, but it's ingress got called to be deleted."));

            return;
        }

        $exists = $client->ingresses()->exists(KubernetesIngressCreate::getKubernetesIngressName($this->id));

        // just exit if there is nothing no need to fail
        if (! $exists) {
            return;
        }

        // TODO how to test any of this?
        $result = $client->ingresses()->deleteByName(KubernetesIngressCreate::getKubernetesIngressName($this->id));

    }
}
