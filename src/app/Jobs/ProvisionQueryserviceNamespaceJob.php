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

        // Docs: https://wiki.blazegraph.com/wiki/index.php/REST_API#CREATE_DATA_SET
        // See: https://github.com/wikimedia/wikidata-query-deploy/blob/8ef3870cb43696d981bea254a6d44ecf5f11f4c9/default.properties
        // See: https://github.com/wikimedia/wikidata-query-deploy/blob/8ef3870cb43696d981bea254a6d44ecf5f11f4c9/createNamespace.sh
        // TODO this needs to match the version deployed?

        // TODO make sure ns is new
        // TODO create the namespace?
        // TODO record the namespace

        //com.bigdata.rdf.sail.namespace=NAMESPACE

/*
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">
<properties>
<entry key="com.bigdata.rdf.sail.namespace">MY_NAMESPACE_NAME</entry>
</properties>
*/

        $qsns = QueryserviceNamespace::create([
          'namespace' => $this->namespace,
          'internalHost' => $this->internalHost,
      ]);
    }
}
