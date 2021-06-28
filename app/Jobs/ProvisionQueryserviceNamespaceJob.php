<?php

namespace App\Jobs;

use App\QueryserviceNamespace;

/**
 * Example usage
 * php artisan wbs-job:handle ProvisionQueryserviceNamespaceJob
 */
class ProvisionQueryserviceNamespaceJob extends Job
{
    private $namespace;
    private $maxFree;

    /**
     * @return void
     */
    public function __construct($namespace = null, $maxFree = null)
    {
        if ($namespace !== null && preg_match('/[^A-Za-z0-9]/', $namespace)) {
            throw new \InvalidArgumentException('$namespace must only contain [^A-Za-z0-9] or null, got '.$namespace);
        }

        // Auto generation and corrections
        // TODO this stuff could come from the model?
        if ($namespace === 'null' || $namespace === null) {
            $namespace = 'qsns_'.substr(bin2hex(random_bytes(24)), 0, 10);
        }

        $this->namespace = $namespace;
        $this->maxFree = $maxFree;
    }

    private function doesMaxFreeSayWeShouldStop(): bool
    {
        $unassignedQueryserviceNamespaces = QueryserviceNamespace::where('wiki_id', null)->count();
        $toCreate = $this->maxFree - $unassignedQueryserviceNamespaces;

        return $toCreate === 0;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // If the job is only meant to create so many DBs, then make sure we don't create too many.
        if ($this->maxFree && $this->doesMaxFreeSayWeShouldStop()) {
            return;
        }

        $properties = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'../data/RWStore.properties');
        // Currently only one, but will change at some point...
        $queryServiceHost = config('app.queryservice_host');

        // Replace the namespace in the properties file
        $properties = str_replace('REPLACE_NAMESPACE', $this->namespace, $properties);

        $curl = curl_init();
        curl_setopt_array($curl, [
            // TODO when there are multiple hosts, this will need to be different?
            // OR go through the gateway?
            CURLOPT_URL => $queryServiceHost.'/bigdata/namespace',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // User agent is needed by the query service...
            CURLOPT_USERAGENT => 'WBStack ProvisionQueryserviceNamespaceJob',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $properties,
            CURLOPT_HTTPHEADER => [
                'content-type: text/plain',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->fail(
                new \RuntimeException('cURL Error #:'.$err)
            );

            return; //safegaurd
        } else {
            if ($response === 'CREATED: '.$this->namespace) {
                $qsns = QueryserviceNamespace::create([
                    'namespace' => $this->namespace,
                    //'internalHost' => $this->internalHost,
                    'backend' => $queryServiceHost,
                ]);
            // TODO error if $qsns is not actually created...
            } else {
                $this->fail(
                    new \RuntimeException('Valid response, but couldn\'t find "CREATED: " in: '.$response)
                );

                return; //safegaurd
            }
            // TODO Else log create failed?
        }
    }
}
