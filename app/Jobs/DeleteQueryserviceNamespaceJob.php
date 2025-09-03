<?php

namespace App\Jobs;

use App\Http\Curl\HttpRequest;
use App\QueryserviceNamespace;
use App\Wiki;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class DeleteQueryserviceNamespaceJob extends Job implements ShouldBeUnique {
    private $wikiId;

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function handle(HttpRequest $request) {
        $wiki = Wiki::withTrashed()->where(['id' => $this->wikiId])->first();

        if (!$wiki) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiId}"));

            return;
        }

        if (!$wiki->deleted_at) {
            $this->fail(new \RuntimeException("Wiki {$this->wikiId} is not marked for deletion."));

            return;
        }

        $qsNamespace = QueryserviceNamespace::whereWikiId($this->wikiId)->first();

        if (!$qsNamespace) {
            $this->fail(new \RuntimeException("Namespace for wiki {$this->wikiId} not found."));

            return;
        }

        $url = $qsNamespace->backend . '/bigdata/namespace/' . $qsNamespace->namespace;

        $request->setOptions([
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => getenv('CURLOPT_TIMEOUT_DELETE_QUERYSERVICE_NAMESPACE') ?: 100,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // User agent is needed by the query service...
            CURLOPT_USERAGENT => 'WBStack DeleteQueryserviceNamespaceJob',
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'content-type: text/plain',
            ],
        ]);

        $response = $request->execute();
        $err = $request->error();

        $request->close();

        if ($err) {
            $this->fail(new \RuntimeException('cURL Error #:' . $err));

            return;
        } else {
            if ($response === 'DELETED: ' . $qsNamespace->namespace) {

                $qsNamespace->delete();

            } else {
                $this->fail(new \RuntimeException('Valid response, but couldn\'t find "DELETED: " in: ' . $response));

                return;
            }
        }
    }
}
