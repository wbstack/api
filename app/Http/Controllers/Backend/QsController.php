<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\QsBatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QsController extends Controller
{
    public function getBatches(Request $request): \Illuminate\Http\Response
    {
        return DB::transaction(function () {
            $oldestBatch = QsBatch::has('wiki')
                ->where([
                    ['done', '=', 0],
                    ['pending_since', '=', null],
                    ['failed', '=', false]
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($oldestBatch === null) {
                return response([]);
            }

            $oldestBatch->update(['pending_since' => Carbon::now()]);
            $oldestBatch->load(['wiki', 'wiki.wikiQueryserviceNamespace']);
            return response([$oldestBatch]);
        }, 3);

    }

    public function markBatchesDone(Request $request): \Illuminate\Http\Response
    {
        $batches = (array) $request->json()->all('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 1, 'pending_since' => null]
        );
        return response('1');
    }

    public function markBatchesNotDone(Request $request): \Illuminate\Http\Response
    {
        $batches = (array) $request->json()->all('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 0, 'pending_since' => null]
        );
        return response('1');
    }
}
