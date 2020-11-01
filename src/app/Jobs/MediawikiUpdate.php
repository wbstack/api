<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;

/**
 * wikidb id 38 is addshore-alpha
 * php artisan wbs-job:handle MediawikiUpdate id,38,mw1.33-wbs5,mw1.34-wbs1,mediawiki-134 ,
 */
class MediawikiUpdate extends Job
{
    private $selectCol;
    private $selectValue;

    private $targetBackendHost;

    private $from;
    private $to;

    /**
     * @param string $selectCol Selection field in the wiki_dbs table e.g. "wiki_id"
     * @param string $selectValue Selection value in the wiki_dbs table e.g. "38"
     * @param string $from The version of schema to update from
     * @param string $to The version of schema to say we updated to
     * @param string $targetBackendHost the backend API hosts to hit (as they are versioned)
     */
    public function __construct($selectCol, $selectValue, $from, $to, $targetBackendHost)
    {
        $this->selectCol = $selectCol;
        $this->selectValue = $selectValue;
        $this->from = $from;
        $this->to = $to;
        $this->targetBackendHost = $targetBackendHost; // TODO this should be less specific, but will do for proof of concept
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Get the Wikidb and Wiki we are operating on
        $wikidb = WikiDb::where($this->selectCol, $this->selectValue)->firstOrFail();

        // Make sure the wikidb is at the expected level
        if ($wikidb->version !== $this->from) {
            throw new \RuntimeException(
          'Wiki Db selected is at different version than expected. '.
          'At: '.$wikidb->version.' Expected: '.$this->from
        );
        }

        // Make sure the wiki is not deleted? and is callable?
        $wiki = $wikidb->wiki;
        // TOOD might need more handeling of soft deleted things?
        if(!$wiki) {
            die('oh noes? db not attached to a wiki currently?');
        }
        $wikiDomain = $wiki->domain;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://'.$this->targetBackendHost.'-app-backend/w/api.php?action=wbstackUpdate&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 60*5,// Longish timeout for such things?
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'host: '.$wikiDomain,
            ],
        ]);

        $rawResponse = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
            throw new \RuntimeException('curl error for '.$wikiDomain.': '.$err);
        }

        curl_close($curl);

        $response = json_decode($rawResponse, true);
        $response = $response['wbstackUpdate'];

        // Look for "Done in" in the response to see success...
        $success = strstr( end($response['output']), "Done in " ) && $response['return'] == 0;

        // Update the DB version if successfull
        if ( $success ) {
            $wikidb->version = $this->to;
            $wikidb->save();
            // TODO update mw verison (so backend requests go to the right place?)
            // TODO update nginx so content is served from the new code too?
        }

        // Output stuff (output is an array)
        echo json_encode($response['output']).PHP_EOL;
        echo json_encode($response['script']).PHP_EOL;
        echo json_encode($response['return']).PHP_EOL;
        echo json_encode("success: " . $success).PHP_EOL;

        // Exception if really bad
        if (!$success) {
            throw new \RuntimeException('wbstackUpdate call for '.$wikiDomain.' was not successful:'.$rawResponse);
        }
    }
}
