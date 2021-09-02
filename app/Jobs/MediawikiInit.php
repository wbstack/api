<?php

namespace App\Jobs;

class MediawikiInit extends Job
{
    private $wikiDomain;
    private $username;
    private $email;

    /**
     * @return void
     */
    public function __construct($wikiDomain, $username, $email)
    {
        $this->wikiDomain = $wikiDomain;
        $this->username = $username;
        $this->email = $email;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $data = [
            'username' => $this->username,
            'email' => $this->email,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => getenv('PLATFORM_MW_BACKEND_HOST').'/w/api.php?action=wbstackInit&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 60,
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
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return; //safegaurd
        }

        curl_close($curl);

        $response = json_decode($rawResponse, true);

        if (!array_key_exists('wbstackInit', $response)) {
            $this->fail(
                new \RuntimeException('wbstackInit call for '.$this->wikiDomain.'. No wbstackInit key in response: '.$rawResponse)
            );

            return; //safegaurd
        }

        if ($response['wbstackInit']['success'] == 0) {
            $this->fail(
                new \RuntimeException('wbstackInit call for '.$this->wikiDomain.' was not successful:'.$rawResponse)
            );

            return; //safegaurd
        }
        // Otherwise there was success (and we could get the userId if we wanted...
    }
}
