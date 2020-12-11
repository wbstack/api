<?php

namespace App\Jobs;

class MediawikiSandboxLoadData extends Job
{
    private $wikiDomain;
    private $dataSet;

    /**
     * @return void
     */
    public function __construct($wikiDomain, $dataSet)
    {
        $this->wikiDomain = $wikiDomain;
        $this->dataSet = $dataSet;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $data = [
            'dataSet' => $this->dataSet,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/rest.php/wikibase-exampledata/v0/load',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10 * 60,// TODO Long 10 mins (probably shouldn't keep the request open...)
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'host: '.$this->wikiDomain,
            ],
        ]);

        $rawResponse = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
            $this->fail(
                new \RuntimeException('wikibase-exampledata/v0/load curl error for '.$this->wikiDomain.': '.$err)
            );
            return;//safegaurd
        }

        curl_close($curl);

        if (!strstr($rawResponse,'Done!')) {
            $this->fail(
                new \RuntimeException('wikibase-exampledata/v0/load call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );
            return;//safegaurd
        }
    }
}
