<?php

namespace App\Jobs;
use App\Wiki;
use App\WikiSetting;
use App\WikiManager;
use App\QueryserviceNamespace;
use App\WikiDb;
use Traversable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\WikiLogoController;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Helper\ElasticSearchHelper;
use App\Http\Curl\HttpRequest;
use Google\Cloud\Core\Exception\GoogleException;

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
            try{
                $elasticSearchBaseName = $wikiDB->name;
                $elasticSearchHost = getenv('ELASTICSEARCH_HOST');
                $elasticSearchHelper = new ElasticSearchHelper($elasticSearchHost, $elasticSearchBaseName);

                if( $elasticSearchHelper->hasIndices( $request ) ) {
                    throw new \RuntimeException("Elasticsearch indices with basename {$elasticSearchBaseName} still exists");
                }
            } catch(\RuntimeException $exception) {
                $this->fail($exception);
                return;
            }

            $this->fail(new \RuntimeException("WikiDb for ${$wiki->id} still exists"));
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
            $disk = Storage::disk('gcs-public-static');
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

        // TODO add support for local files on minikube cluster
        // Probably involves breaking out 'gcs-public-static' into some config setting etc.
        } catch ( GoogleException $ex  ) {
            if( !file_exists( '/var/run/secret/cloud.google.com/key.json' ) ) {
                return true;
            }

            $this->fail($ex);
            return false;

        }
    }
}
