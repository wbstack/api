<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\WikiSetting;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use Illuminate\Support\Facades\Log;
use App\Wiki;

class ElasticSearchIndexInit extends Job implements ShouldBeUnique
{
    private $wikiId;

    /**
     * @return void
     */
    public function __construct( int $wikiId )
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
        $wiki = Wiki::whereId( $this->wikiId )->with('settings')->with('wikiDb')->first();

        // job got triggered but no wiki
        if ( !$wiki ) {
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.' was triggered but not wiki available.') );
            return;
        }

        $setting = $wiki->settings()->where([ 'name' => WikiSetting::wwExtEnableElasticSearch, ])->first();
        // job got triggered but no setting
        if ( !$setting ) {
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.' was triggered but not setting available') );
            return;
        }

        $wikiDB = $wiki->wikiDb()->first();
        // no wikiDB around
        if ( !$wikiDB ) {
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.' was triggered but not WikiDb available') );
            $setting->update( [  'value' => false  ] );
            return;
        }
        
        $request->setOptions(
            [
                CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackElasticSearchInit&format=json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => getenv('CURLOPT_TIMEOUT_ELASTICSEARCH_INIT') ?: 100, // This could potentially take a bit longer
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: '.$wiki->domain,
                ]
            ]
        );

        $rawResponse = $request->execute();
        $err = $request->error();

        if ($err) {
            $this->fail( new \RuntimeException('curl error for '.$this->wikiId.': '.$err) );
            $setting->update( [  'value' => false  ] );
            return;
        }

        $request->close();

        $response = json_decode($rawResponse, true);

        if ( !is_array($response) || !array_key_exists('wbstackElasticSearchInit', $response) ) {
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.'. No wbstackElasticSearchInit key in response: '.$rawResponse) );
            $setting->update( [  'value' => false  ] );
            return;
        }

        if ( !array_key_exists('return', $response['wbstackElasticSearchInit']) || $response['wbstackElasticSearchInit']['return'] !== 0) {
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.' was not successful:'.$rawResponse) );
            $setting->update( [  'value' => false  ] );
            return;
        }

        $output = $response['wbstackElasticSearchInit']['output'];

        $enableElasticSearchFeature = false;

        // occurs a couple of times when newly created
        if ( in_array( "\tCreating index...ok",  $output ) ) {

            // newly created index succeeded, turn on the wiki setting
            $enableElasticSearchFeature = true;

        // occurs on a successful update run
        } else if ( in_array( "\t\tValidating {$wikiDB->name}_general alias...ok", $output ) ) {

            // script ran and update was successful, make sure feature is enabled
            $enableElasticSearchFeature = true;
        } else {

            Log::error(__METHOD__ . ": Job finished but didn't create or update, something is weird");
            $this->fail( new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiId.' was not successful:' . $rawResponse ) );
        }

        $setting->update( [  'value' => $enableElasticSearchFeature  ] );

    }
}
