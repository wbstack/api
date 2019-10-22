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
            // TODO get host from env var...
            CURLOPT_URL => "mediawiki-backend:80/w/api.php?action=wbstackInit&format=json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
                "host: " . $this->wikiDomain
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);
        $response = $response['wbstackInit'];

        if($response['success'] == 0) {
            throw new \RuntimeException('wbstackInit call for ' . $this->wikiDomain . ' was not successful');
        }
        // Otherwise there was success (and we could get the userId if we wanted...
    }
}
