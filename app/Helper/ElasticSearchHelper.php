<?php
namespace App\Helper;
use App\Http\Curl\HttpRequest;

class ElasticSearchHelper
{
    private $elasticSearchHost;
    private $elasticSearchBaseName;

    public function __construct( string $elasticSearchHost, string $elasticSearchBaseName )
    {
        $this->elasticSearchHost = $elasticSearchHost;
        $this->elasticSearchBaseName = $elasticSearchBaseName;
    }

    public function hasIndices( HttpRequest $request ) {
        
        $hasIndices = true;

        // Make an initial request to see if there is anything
        $url = $this->elasticSearchHost."/_cat/indices/{$this->elasticSearchBaseName}*?v&s=index&h=index"; 
        $request->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_DELETE_CHECK') ?: 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]
        );

        $rawResponse = $request->execute();
        $err = $request->error();
        $request->close();
        
        if ( $err ) {
            throw new \RuntimeException('curl error for '.$this->elasticSearchBaseName.': '.$err);
        }

        // Example response:
        // 
        // index\n
        // site1.localhost_content_blabla\n
        // site1.localhost_general_bla\n
        $wikiIndices = array_filter(explode("\n", $rawResponse));

        // no indices to delete only index header
        if( count($wikiIndices) <= 1 ) {
            $hasIndices = false;
        }

        $indexHeader = array_shift($wikiIndices);

        // make sure response is formatted correctly
        if ($indexHeader !== 'index') {
            throw new \RuntimeException("Response looks weird when querying {$url}");
        }

        return $hasIndices;
    }
}
