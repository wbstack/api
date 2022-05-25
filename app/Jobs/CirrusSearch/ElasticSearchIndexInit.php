<?php

namespace App\Jobs\CirrusSearch;

use Illuminate\Support\Facades\Log;

class ElasticSearchIndexInit extends CirrusSearchJob
{
    function apiModule(): string {
        return 'wbstackElasticSearchInit';
    }

    private function logFailureAndDisable(): void {
        $this->setting->update( [  'value' => false  ] );
        Log::warning( __METHOD__ . ": {$this->wiki->domain}: Failed initializing elasticsearch. Disabling the setting." );

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

        $enableElasticSearchFeature = false;

        // occurs a couple of times when newly created
        if ( in_array( "\tCreating index...ok",  $output ) ) {

            // newly created index succeeded, turn on the wiki setting
            $enableElasticSearchFeature = true;

        // occurs on a successful update run
        } else if ( in_array( "\t\tValidating {$this->wikiDB->name}_general alias...ok", $output ) ) {

            // script ran and update was successful, make sure feature is enabled
            $enableElasticSearchFeature = true;
        } else {

            Log::error(__METHOD__ . ": {$this->wiki->domain} Job finished but didn't create or update, something is weird");
            $this->fail( new \RuntimeException($this->apiModule() . ' call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }

        $this->setting->update( [  'value' => $enableElasticSearchFeature  ] );
    }

    protected function getRequestTimeout(): int {
        return getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT') !== false 
            ? intval(getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT')) : parent::getRequestTimeout();
    }
}
