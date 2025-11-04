<?php

namespace App\Jobs\CirrusSearch;

use App\Http\Curl\HttpRequest;
use App\Jobs\Job;
use App\Wiki;
use App\WikiSetting;
use App\Services\MediaWikiHostResolver;
use Illuminate\Contracts\Queue\ShouldBeUnique;

abstract class CirrusSearchJob extends Job implements ShouldBeUnique {
    protected $wikiId;

    protected $setting;

    protected $wiki;

    protected $wikiDB;

    abstract public function apiModule(): string;

    abstract public function handleResponse(string $rawResponse, $error): void;

    public function __construct($wikiId) {
        $this->wikiId = $wikiId;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId() {
        return strval($this->wikiId);
    }

    public function wikiId(): int {
        return $this->wikiId;
    }

    /**
     * @return void
     */
    public function handle(HttpRequest $request, MediaWikiHostResolver $mwHostResolver) {
        $this->wiki = Wiki::whereId($this->wikiId)->with('settings')->with('wikiDb')->first();

        // job got triggered but no wiki
        if (!$this->wiki) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call for ' . $this->wikiId . ' was triggered but not wiki available.'));

            return;
        }

        $this->setting = $this->wiki->settings()->where(['name' => WikiSetting::wwExtEnableElasticSearch])->first();
        // job got triggered but no setting
        if (!$this->setting) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call for ' . $this->wikiId . ' was triggered but not setting available'));

            return;
        }

        $this->wikiDB = $this->wiki->wikiDb()->first();
        // no wikiDB around
        if (!$this->wikiDB) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call for ' . $this->wikiId . ' was triggered but not WikiDb available'));

            return;
        }

        $mwHost = $mwHostResolver->getMwVersionForDomain($this->wiki->domain);

        $request->setOptions(
            [
                CURLOPT_URL => $mwHost . '/w/api.php?action=' . $this->apiModule() . $this->getQueryParams(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => $this->getRequestTimeout(),
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'content-type: application/x-www-form-urlencoded',
                    'host: ' . $this->wiki->domain,
                ],
            ]
        );

        $rawResponse = $request->execute();
        $err = $request->error();
        $request->close();

        $this->handleResponse($rawResponse, $err);
    }

    protected function validateOrFailRequest(?array $response, string $rawResponse, $error): bool {
        if ($error) {
            $this->fail(new \RuntimeException($this->apiModule() . ' curl error for ' . $this->wikiId . ': ' . $error));

            return false;
        }

        if ($this->hasApiError($response)) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call failed with api error: ' . $response['error']['info']));

            return false;
        }

        if (!$this->isValid($response)) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call for ' . $this->wikiId . '. No ' . $this->apiModule() . ' key in response: ' . $rawResponse));

            return false;
        }

        return true;
    }

    protected function validateSuccess(array $response, string $rawResponse, $error): bool {
        if (!$this->isSuccessful($response)) {
            $this->fail(new \RuntimeException($this->apiModule() . ' call for ' . $this->wikiId . ' was not successful:' . $rawResponse));

            return false;
        }

        return true;
    }

    protected function getRequestTimeout(): int {
        return 100;
    }

    // TODO Migrate this to some other baseclass for all internal api classes
    // This and some other stuff would be usedful there too
    protected function hasApiError(?array $response): bool {
        return is_array($response) && array_key_exists('error', $response);
    }

    protected function isValid(?array $response): bool {
        return is_array($response) && array_key_exists($this->apiModule(), $response);
    }

    protected function isSuccessful(array $response): bool {
        return array_key_exists('return', $response[$this->apiModule()]) && $response[$this->apiModule()]['return'] == 0;
    }

    /**
     * @return string
     */
    protected function getQueryParams() {
        return '&format=json';
    }
}
