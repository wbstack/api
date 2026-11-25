<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\QsBatch;
use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QsController extends Controller {
    public function getBatches(Request $request, MediaWikiHostResolver $mwHostResolver): Response {
        return DB::transaction(function () use ($mwHostResolver) {
            $oldestBatch = QsBatch::has('wiki')
                ->where([
                    ['done', '=', 0],
                    ['pending_since', '=', null],
                    ['failed', '=', false],
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($oldestBatch === null) {
                return response([]);
            }

            $oldestBatch->update(['pending_since' => Carbon::now()]);
            $oldestBatch->load(['wiki', 'wiki.wikiQueryserviceNamespace']);

            // queryservice-updater reads from MediaWiki directly
            // platform-nginx is bypassed, which does not serve suspended wikis
            $oldestBatch->setAttribute(
                'mediawiki_backend_host',
                $this->getMediaWikiBackendHost($mwHostResolver, $oldestBatch)
            );

            return response([$oldestBatch]);
        }, 3);

    }

    private function getMediaWikiBackendHost(MediaWikiHostResolver $mwHostResolver, QsBatch $batch): ?string {
        try {
            // Resolving by domain avoids exposing wikiDb credentials in the response
            return $mwHostResolver->getBackendHostForDomain($batch->wiki->domain);
        } catch (UnknownDBVersionException|UnknownWikiDomainException $e) {
            Log::warning('Unable to resolve the MediaWiki backend host for queryservice batch ' . $batch->id . ': ' . $e->getMessage());

            // Keep an unknown host from blocking the queue by returning null
            // allowing the batch to proceed without a resolved backend host
            return null;
        }
    }

    public function markBatchesDone(Request $request): Response {
        $batches = (array) $request->json()->all('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 1, 'pending_since' => null]
        );

        return response('1');
    }

    public function markBatchesNotDone(Request $request): Response {
        $batches = (array) $request->json()->all('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 0, 'pending_since' => null]
        );

        return response('1');
    }
}
