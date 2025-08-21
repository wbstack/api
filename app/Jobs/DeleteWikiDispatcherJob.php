<?php

namespace App\Jobs;

use App\Http\Controllers\WikiController;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDb;
use App\WikiSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteWikiDispatcherJob extends Job {
    /**
     * @return void
     */
    public function handle() {
        $deleteCutoff = Carbon::now()->subDays(
            Config::get('wbstack.wiki_hard_delete_threshold')
        );
        $wikis = Wiki::withTrashed()->whereDate('deleted_at', '<=', $deleteCutoff)->get();

        if (! $wikis->count()) {
            Log::info(__METHOD__ . ': Found no soft deleted wikis over threshold. exiting.');

            return;
        }

        foreach ($wikis as $wiki) {
            Log::info(__METHOD__ . ": Starting hard-delete chain for id: {$wiki->id}, domain: {$wiki->domain}.");

            $jobs = [];

            $namespace = QueryserviceNamespace::whereWikiId($wiki->id)->first();

            // if there is a namespace add the job for that
            if ($namespace) {
                $jobs[] = new DeleteQueryserviceNamespaceJob($wiki->id);
            }

            $elasticSearchSetting = WikiSetting::where([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => true,
            ])->first();

            // if elasticsearch is enabled, add the job for that
            if ($elasticSearchSetting) {
                $jobs[] = new ElasticSearchIndexDelete($wiki->id);
            }

            // if domain is not subdomain remove kubernetes ingress if any
            if (! WikiController::isSubDomain($wiki->domain)) {
                $jobs[] = new KubernetesIngressDeleteJob($wiki->id);
            }

            $wikiDB = WikiDb::whereWikiId($wiki->id)->first();

            // soft-deletes the mediawiki database and removes WikiDB entry
            if ($wikiDB) {
                $jobs[] = new DeleteWikiDbJob($wiki->id);
            }

            // deletes any settings, managers and the wiki itself
            $jobs[] = new DeleteWikiFinalizeJob($wiki->id);

            $logMessage = implode(',', array_map('get_class', $jobs));
            Log::info(__METHOD__ . ": Dispatching hard delete job chain for id: {$wiki->id}, jobs: {$logMessage}.");

            Bus::chain([
                ...$jobs,
            ])->catch(function (Throwable $e) use ($wiki) {
                Log::error(__METHOD__ . "An error occured when deleting {$wiki->id}: " . $e->getMessage());
            })->dispatch();

        }

    }
}
