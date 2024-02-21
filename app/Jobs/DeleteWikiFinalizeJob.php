<?php

namespace App\Jobs;
use App\Wiki;
use App\WikiSetting;
use App\WikiManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Helper\ElasticSearchHelper;
use App\Http\Curl\HttpRequest;

class DeleteWikiFinalizeJob extends Job implements ShouldBeUnique
{
    private $wikiId;

    /**
     * @return void
     */
    public function __construct( $wikiId )
    {
        $this->wikiId = $wikiId;
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
    public function handle( HttpRequest $request )
    {
        $wiki = Wiki::withTrashed()->where('id', $this->wikiId )->first();


        if( !$wiki ) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiId}"));
            return;
        }

        if( !$wiki->deleted_at ) {
            $this->fail(new \RuntimeException("Wiki {$this->wikiId} is not deleted, but job got dispatched."));
            return;
        }

        $wikiDB = $wiki->wikiDb()->first();

        if( $wikiDB ) {
            $elasticSearchHosts = Config::get('wbstack.elasticsearch_hosts');
            foreach ($elasticSearchHosts as $elasticSearchHost) {
                try {
                    $elasticSearchBaseName = $wikiDB->name;
                    $elasticSearchHelper = new ElasticSearchHelper($elasticSearchHost, $elasticSearchBaseName);
                    $request->reset();
                    if( $elasticSearchHelper->hasIndices( $request ) ) {
                        throw new \RuntimeException("Elasticsearch indices with basename {$elasticSearchBaseName} still exists in {$elasticSearchHost}");
                    }
                } catch (\RuntimeException $exception) {
                    $this->fail($exception);
                    continue;
                }

                $this->fail(new \RuntimeException("WikiDb for {$wiki->id} still exists"));
            }
            return;
        }

        // close the curl session
        $request->close();

        $qsNamespace = $wiki->wikiQueryserviceNamespace()->first();

        if( $qsNamespace ) {
            $this->fail(new \RuntimeException("Queryservice namespace for {$wiki->id} still exists"));
            return;
        }

        if ( !$this->deleteSiteDirectory( $wiki->id) ) {
            $this->fail(new \RuntimeException("Failed deleting site directory."));
            return;
        }

        // delete relations
        WikiSetting::whereWikiId($wiki->id)->delete();
        WikiManager::whereWikiId($wiki->id)->delete();

        $wiki->forceDelete();
    }

    public function deleteSiteDirectory( int $wiki_id ): bool {
        try {
            $disk = Storage::disk('static-assets');
            if (! $disk instanceof Cloud) {
                $this->fail(new \RuntimeException("Invalid storage (not cloud)."));
                return false;
            }

            $directory = Wiki::getSiteDirectory( $wiki_id );
            if ( $disk->exists($directory) ) {
                return $disk->deleteDirectory($directory);
            } else {
                return true;
            }

        } catch ( \Exception $ex  ) {
            $this->fail($ex);
            return false;

        }
    }
}
