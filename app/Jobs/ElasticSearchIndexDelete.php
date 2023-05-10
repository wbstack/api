<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\WikiSetting;
use App\Http\Curl\HttpRequest;
use App\Wiki;
use App\Helper\ElasticSearchHelper;
use Illuminate\Support\Facades\Config;

class ElasticSearchIndexDelete extends Job implements ShouldBeUnique
{
    private $wikiId;

    /**
     * @return void
     */
    public function __construct( int $wikiId)
    {
        $this->wikiId = $wikiId;
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
    public function handle( HttpRequest $request )
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

        $primaryElasticSearchHost = Config::get( 'wbstack.primary_elasticsearch_host' );
        if( !$primaryElasticSearchHost ) {
            $this->fail( new \RuntimeException('wbstack.elasticsearch_host not configured') );
            return;
        }

        try {
            $this->deleteIndices( $request, $primaryElasticSearchHost, $elasticSearchBaseName );

            $secondaryElasticSearchHost = Config::get( 'wbstack.secondary_elasticsearch_host' );
            if ( $secondaryElasticSearchHost ) {
                $this->deleteIndices( $request, $secondaryElasticSearchHost, $elasticSearchBaseName );
            }
        } catch ( \RuntimeException $exception ) {
            $this->fail( $exception );
            return;
        }

        $setting->update( [  'value' => false ] );
    }

    /**
     * @param  HttpRequest  $request
     * @param  string $elasticSearchHost
     * @param  string $elasticSearchBaseName
     * @return void
     *
     * @throws \RuntimeException
     */
    private function deleteIndices( $request, $elasticSearchHost, $elasticSearchBaseName ) {
        $elasticSearchHelper = new ElasticSearchHelper( $elasticSearchHost, $elasticSearchBaseName );

        // Not having any indices to remove should not fail the job
        if( !$elasticSearchHelper->hasIndices( $request ) ) {
            return;
        }

        // So there are some indices to delete for the wiki
        //
        // make a request to the elasticsearch cluster using DELETE
        // use cirrusSearch baseName to delete indices
        $url = $elasticSearchHost . "/{$elasticSearchBaseName}*";

        $request->reset();

        $request->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => getenv( 'CURLOPT_TIMEOUT_ELASTICSEARCH_DELETE_DELETE' ) ?: 60,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ]
        );

        $rawResponse = $request->execute();
        $err = $request->error();
        $request->close();

        if ( $err ) {
            throw new \RuntimeException( 'curl error for ' . $this->wikiId . ': ' . $err );
        }

        $response = json_decode( $rawResponse, true );

        if ( !is_array( $response ) || !array_key_exists( 'acknowledged', $response ) || $response[ 'acknowledged' ] !== true ) {
            throw new \RuntimeException( 'ElasticSearchIndexDelete job for ' . $this->wikiId . ' was not successful: ' . $rawResponse );
        }
    }
}
