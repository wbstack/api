<?php

namespace App\Jobs\CirrusSearch;


use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * 
 * Job for queuing batched runs of CirrusSearch ForceSearchIndex.php by using the --buildChunks parameter 
 * 
 * Example:
 * 
 * php artisan job:dispatchNow CirrusSearch\\QueueSearchIndexBatches 1
 */
class QueueSearchIndexBatches extends CirrusSearchJob
{
    use DispatchesJobs;

    function apiModule(): string {
        return 'wbstackQueueSearchIndexBatches';
    }

    protected function getRequestTimeout(): int {
        return 1000;
    }

    public function convertToBatch( $output ): array {

        $batches = [];

        foreach ($output as $command) {
            $matches = [];
            preg_match('/--fromId (\d+) --toId (\d+)/', $command, $matches, PREG_OFFSET_CAPTURE);

            if ( count($matches) !== 3 ) {
                throw new \RuntimeException('Got some weird output from the script: ' . $command);
            }

            $fromId = $matches[1][0];
            $toId = $matches[2][0];

            if( (!is_numeric($fromId) || !is_numeric($toId)) && intVal($fromId) <= intVal($toId) ) {
                throw new \RuntimeException('Batch parameters from command looks weird! fromId: ' . $fromId . ' toId: ' . $toId);
            }

            $batches[] = new ForceSearchIndex( 'id', $this->wikiId, $fromId, $toId );
        }
        
        return $batches;
    }

    public function handleResponse( string $rawResponse, $error ) : void {
        $response = json_decode($rawResponse, true);

        if( !$this->validateOrFailRequest($response, $rawResponse, $error) ) {
            return;
        }

        $output = $response[$this->apiModule()]['output'];

        // if there are no pages to index, just exit now.
        if( !$this->isSuccessful($response) && 
            is_array($output) && count($output) == 1 && $output[0] == "Couldn't find any pages to index.  fromId =  =  = toId." ) {
            Log::warning(__METHOD__ . ": ForceSearchIndex could not find any pages to index. " . $rawResponse);
            return;
        }

        if( !$this->validateSuccess($response, $rawResponse, $error) ) {
            return;
        }

        $batches = [];

        try {
            $batches = $this->convertToBatch( $output );
        } catch (\RuntimeException $e) {
            Log::error(__METHOD__ . ": Failed to convert command output into batched commands: " . $rawResponse);
            $this->fail($e);
            return;
        }

        if ( !empty($batches) ) {
            // todo rewrite as batch
            foreach ($batches as $job) {
                $this->dispatch($job);
            }

        } else {

            Log::error(__METHOD__ . ": Job finished but didn't create any batches, something is weird");
            $this->fail( new \RuntimeException($this->apiModule() . ' call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }

    }
}
