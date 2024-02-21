<?php

namespace App\Jobs\CirrusSearch;

use Illuminate\Support\Facades\Log;
use App\Wiki;

/**
 * 
 * Job for running CirrusSearch/ForceSearchIndex.php on a wiki
 * 
 * Example:
 * 
 * php artisan job:dispatchSync CirrusSearch\\ForceSearchIndex id 1 0 1000
 */
class ForceSearchIndex extends CirrusSearchJob
{
    private $fromId;
    private $toId;

    public function __construct( string $selectCol, $selectValue, int $fromId, int $toId  ) {
        $wiki = Wiki::where($selectCol, $selectValue)->firstOrFail();

        $this->fromId = $fromId;
        $this->toId = $toId;
        parent::__construct($wiki->id);
    }

    public function uniqueId() {
        return parent::uniqueId() . '_'
            . $this->fromId . '_'
            . $this->toId;
    }

    public function fromId(): int {
        return $this->fromId;
    }
    public function toId(): int {
        return $this->toId;
    }

    function apiModule(): string {
        return 'wbstackForceSearchIndex';
    }

    protected function getRequestTimeout(): int {
        return 1000;
    }

    public function handleResponse( string $rawResponse, $error ) : void {
        $response = json_decode($rawResponse, true);

        if( !$this->validateOrFailRequest($response, $rawResponse, $error) ) {
            return;
        }

        if( !$this->validateSuccess($response, $rawResponse, $error) ) {
            return;
        }

        $output = $response[$this->apiModule()]['output'];

        $successMatches = [];
        $lastElement = end($output);
        preg_match('/Indexed a total of (\d+) pages/', $lastElement, $successMatches, PREG_OFFSET_CAPTURE);

        if ( count($successMatches) === 2 && is_numeric($successMatches[1][0]) ) {
            $numIndexedPages = intVal($successMatches[1][0]);
            Log::info(__METHOD__ . ": Finished batch! Indexed {$numIndexedPages} pages. From id {$this->fromId} to {$this->toId}");
        } else {
            dd($successMatches);
            Log::error(__METHOD__ . ": Job finished but did not contain the expected output.");
            $this->fail( new \RuntimeException($this->apiModule() . ' call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }
    }

    /**
     * @return string
     */
    protected function getQueryParams() {
        return parent::getQueryParams() . '&fromId=' . $this->fromId . '&toId=' . $this->toId;
    }
}
