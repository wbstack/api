<?php

namespace App\Jobs;

use App\Wiki;
use Illuminate\Support\Facades\Log;
use App\Http\Curl\HttpRequest;

/*
*
* Job that updates site_stats table in mediawiki by calling initSiteStats.php
*
* Example: php artisan job:dispatchNow SiteStatsUpdateJob
*/
class SiteStatsUpdateJob extends Job
{
    private $wiki_id;

    public function __construct( $wiki_id ) {
        $this->wiki_id = $wiki_id;
    }
    
    public function handle( HttpRequest $request ): void
    {
        $timeStart = microtime(true); 

        $wiki = Wiki::where('id', $this->wiki_id)->first();
        if( !$wiki ) {
            $this->fail( new \RuntimeException(" Could not find wiki with id: $this->wiki_id" ) );
        }

        Log::info(__METHOD__ . ": Updating stats for or $wiki->domain");

        $request->setOptions([
            CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstacksitestatsupdate&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 60 * 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'host: '.$wiki->domain,
            ],
        ]);

        $rawResponse = $request->execute(); 
        $err = $request->error();
        $request->close();

        if ($err) {
            Log::error(__METHOD__ . ": wbstacksitestatsupdate failed: $rawResponse");
            $this->fail(
                new \RuntimeException('curl error for '.$wiki->domain.': '.$err)
            );

            return; //safegaurd
        }

        $response = json_decode($rawResponse, true);

        if ( !is_array($response) || !array_key_exists('wbstacksitestatsupdate', $response) ) {
            $this->fail(
                new \RuntimeException('wbstacksitestatsupdate call for '.$wiki->domain.'. No wbstacksitestatsupdate key in response: '.$rawResponse)
            );

            return; //safegaurd
        }

        if ($response['wbstacksitestatsupdate']['return'] !== 0) {
            $this->fail(
                new \RuntimeException('wbstacksitestatsupdate call for '.$wiki->domain.' was not successful: '.$rawResponse)
            );

            return; //safegaurd
        }

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        Log::info(__METHOD__ . ": Finished in: $executionTime s");


    }
}
