<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QsController extends Controller
{
    public function getBatches(Request $request): \Illuminate\Http\Response
    {
        $notDoneBatches = null;
        $batches = [];
        $returnCollection = [];

        DB::transaction(function () use (&$notDoneBatches, &$batches, &$returnCollection) {
            $notDoneBatches = \App\QsBatch::where('done', 0)->with(['wiki', 'wiki.wikiQueryserviceNamespace'])->get();

            // Get ID of the latest batch that we have created (or 0)
            $maxIdBatch = \App\QsBatch::orderBy('id', 'desc')->first();
            if ($maxIdBatch) {
                $batchesUpToEventId = $maxIdBatch->eventTo;
            } else {
                // If there are no batches then things just hav not really started yet
                $batchesUpToEventId = 0;
            }

            // Get events after the point that batch was created
            // TODO maybe filter by NS here?
            $events = \App\EventPageUpdate::where('id', '>', $batchesUpToEventId)->get();
            $wikiBatchesEntities = [];
            $lastEventId = 0;
            foreach ($events as $event) {
                if (
                    $event->namespace == 120 || // item
                    $event->namespace == 122 || // property
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

                // If we already have a not done batch for this same wiki, then merge that into a new batch
                foreach ($notDoneBatches as $qsBatch) {
                    if ($qsBatch->wiki_id == $wikiId) {
                        $entityBatch = array_merge($entityBatch, explode(',', $qsBatch->entityIds));
                        // Delete the old batch
                        $qsBatch->delete();
                    }
                }

                // Insert the new batch
                $batch = \App\QsBatch::create([
                    'done' => 0,
                    'eventFrom' => $batchesUpToEventId,
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

    public function markBatchesDone(Request $request): \Illuminate\Http\Response
    {
        $rawBatches = $request->input('batches');
        $batches = explode(',', $rawBatches);
        foreach ($batches as $batch) {
            \App\QsBatch::where('id', $batch)
                ->update(['done' => 1]);
        }

        return response(1);
    }

    public function markBatchesFailed(Request $request): \Illuminate\Http\Response
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
