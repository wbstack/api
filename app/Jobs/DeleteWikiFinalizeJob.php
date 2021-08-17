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
        return $this->wikiId;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $wiki = Wiki::withTrashed()->where('id', $this->wikiId )->first();

        if( !$wiki->deleted_at ) {
            $this->fail(new \RuntimeException("Wiki {$this->wikiId} is not deleted, but job got dispatched."));
            return;
        }

        if( !$wiki ) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiId}"));
            return;
        }

        $qsNamespace = $wiki->wikiQueryserviceNamespace()->first();
        $wikiDB = $wiki->wikiDb()->first();


        if( $wikiDB || $qsNamespace ) {
            $this->fail(new \RuntimeException("There are still resources allocated."));
            return;
        }
        
        // if no settings there shouldn't be a need
        $needToDeleteLogosDir = $wiki->settings()->whereIn('name', array(WikiSetting::wgFavicon, WikiSetting::wgLogo))->count() > 0;

        if ( $needToDeleteLogosDir && !$this->deleteLogosDirectory( $wiki->id) ) {
            $this->fail(new \RuntimeException("Failed deleting logos directory."));
            return;
        }

        // delete relations
        WikiSetting::whereWikiId($wiki->id)->delete();
        WikiManager::whereWikiId($wiki->id)->delete();

        $wiki->forceDelete();
    }

    public function deleteLogosDirectory( int $wiki_id ): bool {
        $disk = Storage::disk('gcs-public-static');
        if (! $disk instanceof Cloud) {
            $this->fail(new \RuntimeException("Invalid storage (not cloud)."));
            return false;
        }

        $directory = WikiLogoController::getLogosDirectory( $wiki_id );
        if ( $disk->exists($directory) ) {
            return $disk->deleteDirectory($directory);
        } else {
            return true;
        }
    }
}
