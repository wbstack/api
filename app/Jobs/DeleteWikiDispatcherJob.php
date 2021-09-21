<?php

namespace App\Jobs;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Throwable;
use App\WikiSetting;
use App\QueryserviceNamespace;
use App\Http\Controllers\WikiController;

class DeleteWikiDispatcherJob extends Job
{    
    /**
     * @return void
     */
    public function handle()
    {
        $deleteCutoff = Carbon::now()->subDays(30);
        $wikis = Wiki::withTrashed()->whereDate( 'deleted_at', '<=', $deleteCutoff )->get();

        if( !$wikis->count() ) {
            Log::info( __METHOD__ . ": Found no soft deleted wikis. exiting.");
            return;
        }

        foreach($wikis as $wiki) {
            Log::info( __METHOD__ . ": Dispatching hard delete job chain for id: {$wiki->id}, domain: {$wiki->domain}.");

            $jobs = [];

            $namespace = QueryserviceNamespace::whereWikiId($wiki->id)->first();

            // if there is a namespace add the job for that
            if( $namespace ) {
                $jobs[] = new DeleteQueryserviceNamespaceJob( $namespace->id );
            }

            $elasticSearchSetting = WikiSetting::where([ 'wiki_id' => $wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, ])->first();
            
            // if elasticsearch was used, add the job for that
            if( $elasticSearchSetting ) {
                $jobs[] = new ElasticSearchIndexDelete( $wiki->id );
            }

            // if domain is not subdomain remove kubernetes ingress if any
            if ( !WikiController::isSubDomain( $wiki->domain ) ) {
                $jobs[] = new KubernetesIngressDeleteJob( $wiki->id );
            }

            Bus::chain([
                ...$jobs,
                new DeleteWikiDbJob( $wiki->id ), // deletes the mysql database and WikiDB entry
                new DeleteWikiFinalizeJob( $wiki->id ) // deletes any settings, managers and the wiki itself
            ])->catch(function (Throwable $e ) use ( $wiki ) {
                Log::error( __METHOD__ . "An error occured when deleting {$wiki->id}: " . $e->getMessage());
                $this->fail($e);
            })->dispatch();

        }
   
    }
}
