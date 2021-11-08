<?php

namespace App\Jobs;
use App\Http\Curl\HttpRequest;

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
    public function handle( HttpRequest $request )
    {
        $data = [
            'username' => $this->username,
            'email' => $this->email,
        ];

        $request->setOptions([
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

        $rawResponse = $request->execute(); 
        $err = $request->error();
        $request->close();

        if ($err) {
            $this->fail(
                new \RuntimeException('curl error for '.$this->wikiDomain.': '.$err)
            );

            return; //safegaurd
        }

        $response = json_decode($rawResponse, true);

        if ( !is_array($response) || !array_key_exists('wbstackInit', $response) ) {
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
