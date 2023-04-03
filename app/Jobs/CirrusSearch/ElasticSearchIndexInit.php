<?php

namespace App\Jobs\CirrusSearch;

use Illuminate\Support\Facades\Log;

class ElasticSearchIndexInit extends CirrusSearchJob
{
    function apiModule(): string {
        return 'wbstackElasticSearchInit';
    }

    private function logFailureAndDisable(): void {
        Log::warning( __METHOD__ . ": Failed initializing elasticsearch. Disabling the setting." );
    }

    public function handleResponse( string $rawResponse, $error ): void
    {
        $response = json_decode( $rawResponse, true );

        if( !$this->validateOrFailRequest($response, $rawResponse, $error) ) {
            $this->logFailureAndDisable();
            return;
        }

        if (!$this->validateSuccess($response, $rawResponse, $error)) {
            $this->logFailureAndDisable();
            return;
        }

        $output = $response[$this->apiModule()]['output'];

        // newly created index failed, script ran and update was unsuccessful, then error.
        if ( !(
            in_array( "\tCreating index...ok",  $output ) ||
            in_array( "\t\tValidating {$this->wikiDB->name}_general alias...ok", $output )
        ) ) {

            Log::error(__METHOD__ . ": Job finished but didn't create or update, something is weird");
            $this->fail( new \RuntimeException($this->apiModule() . ' call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }
    }

    protected function getRequestTimeout(): int {
        return getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT') !== false
            ? intval(getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT')) : parent::getRequestTimeout();
    }
}
