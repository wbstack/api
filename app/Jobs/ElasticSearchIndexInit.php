<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\WikiSetting;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use Illuminate\Support\Facades\Log;

class ElasticSearchIndexInit extends Job implements ShouldBeUnique
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
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.' was triggered but not setting available.')
            );

            return;
        }
        
        $this->request->setOptions( 
            [
                CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackElasticSearchInit&format=json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: '.$this->wikiDomain,
                ]
            ]
        );

        $rawResponse = $this->request->execute();
        $err = $this->request->error();

        if ($err) {
            $this->fail(
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return;
        }

        $this->request->close();

        $response = json_decode($rawResponse, true);

        if (!array_key_exists('wbstackElasticSearchInit', $response)) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.'. No wbstackElasticSearchInit key in response: '.$rawResponse)
            );

            return;
        }

        if ($response['wbstackElasticSearchInit']['success'] == 0) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );

            return;
        }

        $output = $response['wbstackElasticSearchInit']['output'];

        $newlyCreated = "\tCreating index...ok"; // occurs a couple of times when newly created
        $updated = "\t\tValidating {$this->wikiDomain}_general alias...ok"; // occurs on a successful update run

        $enableElasticSearchFeature = false;

        if ( in_array( $newlyCreated,  $output ) ) {

            // newly created index succeeded, turn on the wiki setting
            $enableElasticSearchFeature = true;

        } else if ( in_array( $updated, $output ) ) {

            // script ran and update was successful, make sure feature is enabled
            $enableElasticSearchFeature = true;
        }else {

            Log::error(__METHOD__ . ": Job finished but didn't create or update, something is weird");
            $this->fail(
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );
        }

        $setting->update(
            [ 
                'value' => $enableElasticSearchFeature 
            ]
        );

    }
}
