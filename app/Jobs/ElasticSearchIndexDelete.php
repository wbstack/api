<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\WikiSetting;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;

class ElasticSearchIndexDelete extends Job implements ShouldBeUnique
{
    private $wikiDomain;
    private $wikiId;

    private $request;

    /**
     * @return void
     */
    public function __construct( string $wikiDomain, int $wikiId, HttpRequest $request = null )
    {
        $this->wikiDomain = $wikiDomain;
        $this->wikiId = $wikiId;
        $this->request = $request ?? new CurlRequest();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->wikiDomain;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $setting = WikiSetting::where([ 'wiki_id' => $this->wikiId, 'name' => WikiSetting::wwExtEnableElasticSearch, ])->first();

        // job got triggered but no setting in database
        if ( $setting === null ) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchDelete call for '.$this->wikiDomain.' was triggered but not setting available.')
            );

            return;
        }
        $elasticSearchBaseName = $this->wikiDomain;
        $elasticSearchHost = getenv('ELASTICSEARCH_HOST');
        
        if( !$elasticSearchHost ) {
            $this->fail( new \RuntimeException('ELASTICSEARCH_HOST not configured') );
            return;
        }

        $url = $elasticSearchHost."/_cat/indices/{$elasticSearchBaseName}*?v&s=index&h=index";
                
        $this->request->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]
        );

        $rawResponse = $this->request->execute();
        $err = $this->request->error();
        
        if ($err ) {
            $this->fail(
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return;
        }

        // Example response:
        // 
        // index\n
        // site1.localhost_content_blabla\n
        // site1.localhost_general_bla\n
        $wikiIndices = array_filter(explode("\n", $rawResponse));

        // no indices to delete
        if( count($wikiIndices) <= 1 ) {

            // update setting to be disabled
            $setting->update( [  'value' => false ] );

            $this->fail(
                new \RuntimeException("No index to remove for {$this->wikiDomain}")
            );

            return;
        }

        $indexHeader = array_shift($wikiIndices);

        // make sure response is formatted correctly
        if ($indexHeader !== 'index') {
            $this->fail(
                new \RuntimeException("Response looks weird when querying {$url}")
            );

            return;
        }

        // So there are some indices to delete for the wiki
        //
        // make a request to the elasticsearch cluster using DELETE
        // use cirrusSearch baseName to delete indexes
        //
        // TODO should probably use the wiki_id instead as basename
        $url = $elasticSearchHost."/{$elasticSearchBaseName}*";

        $this->request->reset();

        $this->request->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ]
        );

        $rawResponse = $this->request->execute();
        $err = $this->request->error();
        $this->request->close();

        if ($err ) {
            $this->fail(
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return;
        }

        $response = json_decode($rawResponse, true);

        if (!array_key_exists('acknowledged', $response) || $response['acknowledged'] !== true) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchDelete call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );

            return;
        }

        $setting->update( [  'value' => false ] );
    }
}
