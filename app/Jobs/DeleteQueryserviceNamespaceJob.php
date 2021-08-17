<?php

namespace App\Jobs;

use App\QueryserviceNamespace;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class DeleteQueryserviceNamespaceJob extends Job implements ShouldBeUnique
{
    private $wikiId;
    private $request;

    /**
     * @return void
     */
    public function __construct( $wikiId, HttpRequest $request = null)
    {
        $this->wikiId = $wikiId;
        $this->request = $request ?? new CurlRequest();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return strval($this->wikiId);
    }

    /**
     * @return void
     */
    public function handle()
    {
        $qsNamespace = QueryserviceNamespace::whereWikiId($this->wikiId)->first();

        if( !$qsNamespace ) {
            $this->fail( new \RuntimeException("Namespace for wiki {$this->wikiId} not found.") );
            return;
        }

        $queryServiceHost = config('app.queryservice_host');

        $this->request->setOptions([
            CURLOPT_URL => $queryServiceHost.'/bigdata/namespace/' . $qsNamespace->namespace ,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // User agent is needed by the query service...
            CURLOPT_USERAGENT => 'WBStack DeleteQueryserviceNamespaceJob',
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'content-type: text/plain',
            ],
        ]);

        $response = $this->request->execute(); 
        $err = $this->request->error();

        $this->request->close();

        if ($err) {
            $this->fail( new \RuntimeException('cURL Error #:'.$err) );
            return;
        } else {
            if ($response === 'DELETED: '.$qsNamespace->namespace) {

                QueryserviceNamespace::where([
                    'namespace' => $qsNamespace->namespace,
                    'backend' => $queryServiceHost,
                ])->delete();

            } else {
                $this->fail(
                    new \RuntimeException('Valid response, but couldn\'t find "DELETED: " in: '.$response)
                );

                return;
            }
        }
    }
}
