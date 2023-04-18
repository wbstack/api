<?php

namespace App\Jobs\CirrusSearch;

use Illuminate\Support\Facades\Log;

class ElasticSearchIndexInit extends CirrusSearchJob
{
    function apiModule(): string {
        return 'wbstackElasticSearchInit';
    }

    private function logFailure(): void {
        Log::error( __METHOD__ . ": Failed initializing elasticsearch." );
    }

    public function handleResponse( string $rawResponse, $error ): void
    {
        $response = json_decode( $rawResponse, true );

        if( !$this->validateOrFailRequest($response, $rawResponse, $error) ) {
            $this->logFailure();
            return;
        }

        if (!$this->validateSuccess($response, $rawResponse, $error)) {
            $this->logFailure();
            return;
        }

        $output = $response[$this->apiModule()]['output'];

        // if newly created index failed, script ran and update was unsuccessful, then log error.
        if ( !(
            in_array( "\tCreating index...ok",  $output ) ||
            in_array( "\t\tValidating {$this->wikiDB->name}_general alias...ok", $output )
        ) ) {

            Log::error(__METHOD__ . ": Job finished but didn't create or update, something is weird");
            $this->logFailure();
            $this->fail( new \RuntimeException($this->apiModule() . ' call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }
    }

    protected function getRequestTimeout(): int {
        return getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT') !== false
            ? intval(getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT')) : parent::getRequestTimeout();
    }
}
