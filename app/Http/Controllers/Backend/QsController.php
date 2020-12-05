<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class QsController extends Controller
{
    public function getBatches(Request $request)
    {
        $notDoneBatches = null;
        $batches = [];
        $returnCollection = [];

        DB::transaction(function () use (&$notDoneBatches, &$batches, &$returnCollection) {
//            // Get batches that have not yet been done
            $notDoneBatches = \App\QsBatch::where('done', 0)->with(['wiki', 'wiki.wikiQueryserviceNamespace'])->get();
//
//            // Find out which events batches have been generated up to for
//            $batchesUpTo = 0;
//            foreach($notDoneBatches as $qsBatch) {
//                if($qsBatch->eventTo > $batchesUpTo) {
//                    $batchesUpTo = $qsBatch->eventTo;
//                }
//            }

            // If there are no pending batches, get the highest ID so we don't make more..
            //if($batchesUpTo == 0) {
            $maxIdBatch = \App\QsBatch::orderBy('id', 'desc')->first();
            if ($maxIdBatch) {
                $batchesUpTo = $maxIdBatch->eventTo;
            } else {
                die('sometihng went wrong 666778');
            }
            //}

            // Get events after that point and batch by wiki_id
            // TODO maybe filter by NS here?
            $events = \App\EventPageUpdate::where('id', '>', $batchesUpTo)->get();
            $wikiBatchesEntities = [];
            $lastEventId = 0;
            foreach ($events as $event) {
                if (
                    $event->namespace == 120 ||// item
                    $event->namespace == 122 ||// property
                    $event->namespace == 146// lexeme
                ) {
                    $wikiBatchesEntities[$event->wiki_id][] = $event->title;
                }
                if ($event->id > $lastEventId) {
                    $lastEventId = $event->id;
                }
            }

            // Inset the newly created batches into the table...
            foreach ($wikiBatchesEntities as $wikiId => $entityBatch) {

                // If we already have a not done event for the same wiki
                foreach ($notDoneBatches as $qsBatch) {
                    if ($qsBatch->wiki_id == $wikiId) {
                        $entityBatch = array_merge($entityBatch, explode(',', $qsBatch->entityIds));
                        $qsBatch->delete();
                    }
                }

                $batch = \App\QsBatch::create([
                    'done' => 0,
                    'eventFrom' => $batchesUpTo,
                    'eventTo'=> $lastEventId,
                    'wiki_id' => $wikiId,
                    'entityIds' => implode(',', array_unique($entityBatch)),
                ]);
                // TODO to the loading on all batches at once? :D
                $batch->load(['wiki', 'wiki.wikiQueryserviceNamespace']);
                $batches[] = $batch;
            }

            // $batches is all the batches, but lets just return one
            // and for now mark it as done
            /** @var Collection $batches */
            $sorted = collect($batches)->sortBy('id');

            if ($sorted->isEmpty()) {
                /**
                 * If sorted collection is empty, then look back at the still $notDoneBatches
                 * and just shove them in...
                 * This should be done better.
                 */
                $sorted = $notDoneBatches;
            }

            $first = $sorted->first();

            if ($first === null) {
                $returnCollection = [];
            } else {
                $first->update(['done' => 1]);
                $returnCollection = [$first];
            }
        });

        return response(collect($returnCollection));
    }

    public function markBatchesDone(Request $request)
    {
        $rawBatches = $request->input('batches');
        $batches = explode(',', $rawBatches);
        foreach ($batches as $batch) {
            \App\QsBatch::where('id', $batch)
                ->update(['done' => 1]);
        }

        return response(1);
    }

    public function markBatchesFailed(Request $request)
    {
        $rawBatches = $request->input('batches');
        $batches = explode(',', $rawBatches);
        foreach ($batches as $batch) {
            \App\QsBatch::where('id', $batch)
                ->update(['done' => 0]);
        }

        return response(1);
    }
}
