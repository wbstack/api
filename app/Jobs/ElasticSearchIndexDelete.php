<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\WikiSetting;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use App\Wiki;

class ElasticSearchIndexDelete extends Job implements ShouldBeUnique
{
    private $wikiId;

    private $request;

    /**
     * @return void
     */
    public function __construct( int $wikiId, HttpRequest $request = null )
    {
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
        return strval($this->wikiId);
    }

    /**
     * @return void
     */
    public function handle()
    {
        $wiki = Wiki::withTrashed()->where( [ 'id' => $this->wikiId ] )->with('settings')->with('wikiDb')->first();

        if ( !$wiki ) {
            $this->fail( new \RuntimeException('ElasticSearchIndexDelete job for '.$this->wikiId.' was triggered but no wiki available.') );
            return;
        }

        if ( !$wiki->deleted_at ) {
            $this->fail( new \RuntimeException('ElasticSearchIndexDelete job for '.$this->wikiId.' but that wiki is not marked as deleted.') );
            return;
        }

        $setting = $wiki->settings()->where([ 'name' => WikiSetting::wwExtEnableElasticSearch, ])->first();
        if ( !$setting ) {
            $this->fail( new \RuntimeException('ElasticSearchIndexDelete job for '.$this->wikiId.' was triggered but no setting available') );
            return;
        }

        $wikiDB = $wiki->wikiDb()->first();
        if ( !$wikiDB ) {
            $this->fail( new \RuntimeException('ElasticSearchIndexDelete job for '.$this->wikiId.' was triggered but no WikiDb available') );
            $setting->update( [  'value' => false  ] );
            return;
        }
        
        $elasticSearchBaseName = $wikiDB->name;
        $elasticSearchHost = getenv('ELASTICSEARCH_HOST');
        
        if( !$elasticSearchHost ) {
            $this->fail( new \RuntimeException('ELASTICSEARCH_HOST not configured') );
            return;
        }

        // Make an initial request to see if there is anything
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
        
        if ( $err ) {
            $this->fail( new \RuntimeException('curl error for '.$this->wikiId.': '.$err) );
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

            $this->fail( new \RuntimeException("No index to remove for {$this->wikiId}") );
            return;
        }

        $indexHeader = array_shift($wikiIndices);

        // make sure response is formatted correctly
        if ($indexHeader !== 'index') {
            $this->fail( new \RuntimeException("Response looks weird when querying {$url}") );
            return;
        }

        // So there are some indices to delete for the wiki
        //
        // make a request to the elasticsearch cluster using DELETE
        // use cirrusSearch baseName to delete indices
        $url = $elasticSearchHost."/{$elasticSearchBaseName}*";

        $this->request->reset();

        $this->request->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ]
        );

        $rawResponse = $this->request->execute();
        $err = $this->request->error();
        $this->request->close();

        if ($err ) {
            $this->fail( new \RuntimeException('curl error for '.$this->wikiId.': '.$err) );

            return;
        }

        $response = json_decode($rawResponse, true);

        if ( !is_array($response) || !array_key_exists('acknowledged', $response) || $response['acknowledged'] !== true) {
            $this->fail( new \RuntimeException('ElasticSearchIndexDelete job for '.$this->wikiId.' was not successful: '.$rawResponse) );
            return;
        }

        $setting->update( [  'value' => false ] );
    }
}
