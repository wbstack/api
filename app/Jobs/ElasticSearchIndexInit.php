<?php

namespace App\Jobs;

class ElasticSearchIndexInit extends Job
{
    private $wikiDomain;

    /**
     * @return void
     */
    public function __construct($wikiDomain)
    {
        $this->wikiDomain = $wikiDomain;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackElasticSearchInit&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'host: '.$this->wikiDomain,
            ],
        ]);

        $rawResponse = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
            $this->fail(
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return; //safegaurd
        }

        curl_close($curl);

        $response = json_decode($rawResponse, true);

        if (!array_key_exists('wbstackElasticSearchInit', $response)) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.'. No wbstackElasticSearchInit key in response: '.$rawResponse)
            );

            return; //safegaurd
        }

        if ($response['wbstackElasticSearchInit']['success'] == 0) {
            $this->fail(
                new \RuntimeException('wbstackElasticSearchInit call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );

            return; //safegaurd
        }
    }
}
