<?php

namespace App\Jobs;

use App\WikiDb;

/**
 * NEW (Nov 2020) was of performing Mediawiki update.
 * This script was written for and used for MediaWiki 1.33 -> 1.35.
 * This script calls out to a backend custom API module in MediaWiki that runs update.php.
 *
 * Gotchas:
 *  - The script and API has a timeout of  mins, so if big changes are needed it can fail...
 *
 * Usage:
 *
 * wikidb id 38 is addshore-alpha
 * php artisan job:dispatchSync MediawikiUpdate wikis.id 38 mw1.34-wbs1 mw1.35-wbs1 mediawiki-135
 *
 * If you want to update any random wiki then...
 * php artisan job:dispatchSync MediawikiUpdate wiki_dbs.version mw1.34-wbs1 mw1.34-wbs1 mw1.35-wbs1 mediawiki-135
 *
 * And loop them (10 at a time)
 * for i in {1..10}; do php artisan job:dispatchSync MediawikiUpdate wiki_dbs.version mw1.34-wbs1 mw1.34-wbs1 mw1.35-wbs1 mediawiki-135; done
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
        // TODO in an ideal world the target backend would be known by the application somehow?
        $this->targetBackendHost = $targetBackendHost;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Get the Wikidb and Wiki we are operating on, where the wiki is NOT deleted
        $wikidb = WikiDb::where($this->selectCol, $this->selectValue)
            ->select('wiki_dbs.*') // Needed to avoid confusing the update later?! =o https://stackoverflow.com/a/56141702/4746236
            ->leftJoin('wikis', 'wiki_id', '=', 'wikis.id')
            ->whereNull('wikis.deleted_at')
            ->firstOrFail();

        // Make sure the wikidb is at the expected version
        if ($wikidb->version !== $this->from) {
            $this->fail(
                new \RuntimeException(
                    'Wiki Db selected is at different version than expected. '.
                    'At: '.$wikidb->version.' Expected: '.$this->from
                )
            );

            return; //safegaurd
        }

        $wiki = $wikidb->wiki;
        $wikiDomain = $wiki->domain;

        // Make a request to the backend MW API to perform the update.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://'.$this->targetBackendHost.'-app-backend/w/api.php?action=wbstackUpdate&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 60 * 60, // Longish timeout for such things?
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
            $this->fail(
                new \RuntimeException('curl error for '.$wikiDomain.': '.$err)
            );

            return; //safegaurd
        }

        curl_close($curl);

        $response = json_decode($rawResponse, true);
        $response = $response['wbstackUpdate'];

        // Look for "Done in" in the response to see success...
        // This is normally the last line of update.php output
        $success = strstr(end($response['output']), 'Done in ') && $response['return'] == 0;

        // Update the DB version if successfull
        if ($success) {
            $wikidb->version = $this->to;
            $wikidb->save();
            // TODO update mw verison (so backend requests go to the right place?)
            // TODO update nginx so content is served from the new code too?
            // Note: This probably actually needs nginx / something in front of services to be dynamically fetching settings etc.
        }

        // Output stuff (output is an array)
        echo json_encode($response['output']).PHP_EOL;
        echo json_encode($response['script']).PHP_EOL;
        echo json_encode($response['return']).PHP_EOL;
        echo json_encode($wikiDomain).PHP_EOL;
        echo json_encode('success: '.$success).PHP_EOL;

        // Exception if really bad
        if (! $success) {
            $this->fail(
                new \RuntimeException('wbstackUpdate call for '.$wikiDomain.' was not successful:'.$rawResponse)
            );

            return; //safegaurd
        }
    }
}
