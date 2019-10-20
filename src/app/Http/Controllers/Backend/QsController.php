<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QsController extends Controller
{
    public function getBatches(Request $request)
    {
        $notDoneBatches = null;
        $batches = [];

        DB::transaction(function () use ( &$notDoneBatches, &$batches ) {
            // Get batches that have not yet been done
            $notDoneBatches = \App\QsBatch::where('done', 0)->with(['wiki','wiki.wikiQueryserviceNamespace'])->get();

            // Find out which events batches have been generated up to for
            $batchesUpTo = 0;
            foreach($notDoneBatches as $qsBatch) {
                if($qsBatch->eventTo > $batchesUpTo) {
                    $batchesUpTo = $qsBatch->eventTo;
                }
            }
            // If there are no pending batches, get the highest ID so we don't make more..
            if($batchesUpTo == 0) {
                $maxIdBatch = \App\QsBatch::orderBy('id', 'desc')->first();
                if($maxIdBatch) {
                    $batchesUpTo = $maxIdBatch->eventTo;
                }
            }

            // Get events after that point and batch by wiki_id
            // TODO maybe filter by NS here?
            $events = \App\EventPageUpdate::where('id', '>', $batchesUpTo)->get();
            $wikiBatchesEntities = [];
            $lastEventId = 0;
            foreach( $events as $event ) {
                if($event->namespace == 120 || $event->namespace == 122) {
                    $wikiBatchesEntities[$event->wiki_id][] = $event->title;
                }
                if($event->id > $lastEventId) {
                    $lastEventId = $event->id;
                }
            }

            // Inset the newly created batches into the table...
            foreach($wikiBatchesEntities as $wikiId => $entityBatch) {

                $batch = \App\QsBatch::create([
                    'done' => 0,
                    'eventFrom' => $batchesUpTo,
                    'eventTo'=> $lastEventId,
                    'wiki_id' => $wikiId,
                    'entityIds' => implode( ',', array_unique( $entityBatch ) )
                ]);
                // TODO to the loading on all batches at once? :D
                $batch->load(['wiki', 'wiki.wikiQueryserviceNamespace']);
                $batches[] = $batch;
            }
        } );

        /** @var Collection $notDoneBatches */
        return response($notDoneBatches->merge($batches));
    }

    public function markBatchesDone(Request $request)
    {
        $rawBatches = $request->input('batches');
        $batches = explode(',', $rawBatches);
        foreach($batches as $batch) {
            \App\QsBatch::where( 'id', $batch )
                ->update(['done' => 1]);
        }
        return response(1);
    }
}
