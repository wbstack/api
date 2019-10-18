<?php

namespace App\Jobs;

use App\QueryserviceNamespace;

class ProvisionQueryserviceNamespaceJob extends Job
{

    private $namespace;
    private $internalHost;

    /**
     * @return void
     */
    public function __construct($namespace = null, $internalHost = null)
    {
        if ($namespace !== null && preg_match('/[^A-Za-z0-9]/', $namespace)) {
            throw new \InvalidArgumentException('$namespace must only contain [^A-Za-z0-9] or null, got '.$namespace);
        }
        if ($internalHost !== null && preg_match('/[^A-Za-z0-9]/', $internalHost)) {
            throw new \InvalidArgumentException('$internalHost must only contain [^A-Za-z0-9] or null, got '.$internalHost);
        }

        // Auto generation and corrections
        // TODO this stuff could come from the model?
        if ($namespace === 'null' || $namespace === null) {
            $namespace = 'qsns_'.substr(bin2hex(random_bytes(24)), 0, 10);
        }
        if ($internalHost === 'null' || $internalHost === null) {
            $internalHost = 'qshost_'.substr(bin2hex(random_bytes(24)), 0, 10);
        }
        $this->namespace = $namespace;
        $this->internalHost = $internalHost;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $properties = file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . '../data/RWStore.properties' );
        // Currently only one, but will change at some point...
        $queryServiceHost = config( 'app.queryservice_host' );

        // Replace the namespace in the properties file
        $properties = str_replace( 'REPLACE_NAMESPACE', $this->namespace, $properties );

        $curl = curl_init();
        curl_setopt_array($curl, [
            // TODO when there are multiple hosts, this will need to be different?
            // OR go through the gateway?
            CURLOPT_URL => $queryServiceHost . "/bigdata/namespace",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $properties,
            CURLOPT_HTTPHEADER => [
                "content-type: text/plain",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
            return;
        } else {
            if( $response === 'CREATED: ' . $this->namespace) {
                $qsns = QueryserviceNamespace::create([
                    'namespace' => $this->namespace,
                    'internalHost' => $this->internalHost,
                    'backend' => $queryServiceHost,
                ]);
            }
            // TODO Else log create failed?
        }

    }
}
