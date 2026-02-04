<?php

namespace App\Jobs;

use App\Http\Curl\HttpRequest;
use App\WikiDb;
use Illuminate\Support\Facades\Log;

class ElasticSearchAliasInit extends Job {
    private $wikiId;

    private $esHost;

    private $dbName;

    private $sharedPrefix;

    /**
     * @param  string  $dbName
     */
    public function __construct(int $wikiId, string $esHost, ?string $sharedPrefix = null) {
        $this->wikiId = $wikiId;
        $this->esHost = $esHost;
        $this->sharedPrefix = $sharedPrefix ?? getenv('ELASTICSEARCH_SHARED_INDEX_PREFIX');
    }

    public function handle(HttpRequest $request) {
        Log::info(__METHOD__ . ": Updating Elasticsearch aliases for $this->wikiId");

        if (!$this->sharedPrefix) {
            Log::error(__METHOD__ . ": Missing shared index prefix for $this->wikiId");
            $this->fail(
                new \RuntimeException("Missing shared index prefix for $this->wikiId")
            );

            return;
        }

        Log::info(__METHOD__ . ": Using '$this->sharedPrefix' as the shared prefix for $this->wikiId");

        $this->dbName = WikiDb::where('wiki_id', $this->wikiId)->pluck('name')->first();
        if (!$this->dbName) {
            Log::error(__METHOD__ . ": Failed to get database name for $this->wikiId");
            $this->fail(
                new \RuntimeException("Failed to get database name for $this->wikiId")
            );

            return;
        }

        $actions = [];
        foreach (['content', 'general'] as $index) {
            $notAliasedIndex = $this->sharedPrefix . '_' . $index . '_first';
            $filter = $this->dbName . '-';
            $aliases = [
                $this->dbName,
                $this->dbName . '_' . $index,
                $this->dbName . '_' . $index . '_first',
            ];

            foreach ($aliases as $alias) {
                array_push($actions, [
                    'add' => [
                        'index' => $notAliasedIndex,
                        'alias' => $alias,
                        'routing' => $alias,
                        'filter' => ['prefix' => ['wiki' => $filter]],
                    ],
                ]);
            }
        }

        $request->setOptions([
            CURLOPT_URL => $this->esHost . '/_aliases',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60 * 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['actions' => $actions]),
        ]);

        $rawResponse = $request->execute();
        $error = $request->error();
        $request->close();

        if ($error) {
            Log::error(__METHOD__ . ": Updating Elasticsearch aliases failed for $this->wikiId with $rawResponse");
            $this->fail(
                new \RuntimeException("cURL errored for $this->wikiId with $error")
            );

            return;
        }

        $json = json_decode($rawResponse, true);
        if ($json['acknowledged'] !== true) {
            Log::error(__METHOD__ . ": Updating Elasticsearch aliases failed for $this->wikiId with $rawResponse");
            $this->fail(
                new \RuntimeException("Updating Elasticsearch aliases failed for $this->wikiId with $rawResponse")
            );

            return;
        }

        Log::info(__METHOD__ . ": Updating Elasticsearch aliases finished for $this->wikiId");
    }
}
