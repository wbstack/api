<?php

namespace App\Jobs;

use App\Wiki;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\ConfigMap;

class UpdateQueryserviceAllowList extends Job
{
    public function handle(Client $k8s)
    {
        $allowList = implode(
            PHP_EOL,
            array_map(
                fn($domain) => "https://{$domain}/query/sparql",
                Wiki::all()->pluck('domain')->toArray()
            )
        );

        $k8s->setNamespace('default');
        $configName = 'queryservice-allowlist';
        $config = $k8s->configMaps()->setFieldSelector([
            'metadata.name' => $configName
        ])->first();

        if ($config === null) {
            $this->fail(
                new \RuntimeException(
                    "Queryservice config map '{$configName}' does not exist."
                )
            );
            return;
        }

        $allowListKey = 'allowlist.txt';
        $allowListStaticKey = 'allowlist-static.txt';

        $config = $config->toArray();
        if (array_key_exists($allowListStaticKey, $config['data'])) {
            $allowList .= PHP_EOL . trim($config['data'][$allowListStaticKey]);
        }
        $config['data'][$allowListKey] = trim($allowList);
        $k8s->configMaps()->update(new ConfigMap($config));
    }
}
