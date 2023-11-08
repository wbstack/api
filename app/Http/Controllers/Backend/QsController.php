<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\QsBatch;
use Carbon\Carbon;
use App\EventPageUpdate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QsController extends Controller
{
    public function getBatches(Request $request): \Illuminate\Http\Response
    {
        return DB::transaction(function () {
            // Get ID of the latest batch that we have created (or 0)
            $maxIdBatch = QsBatch::orderBy('id', 'desc')->first();
            if ($maxIdBatch) {
                $batchesUpToEventId = $maxIdBatch->eventTo;
            } else {
                // If there are no batches then things just hav not really started yet
                $batchesUpToEventId = 0;
            }

            // Get events after the point that batch was created
            // TODO maybe filter by NS here?
            $events = EventPageUpdate::where('id', '>', $batchesUpToEventId)->get();
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

            /** @var Collection $newlyCreatedBatches */
            $newlyCreatedBatches = [];
            $notDoneBatches = QsBatch::where([
                ['done', '=', 0], ['pending_since', '=', null], ['failed', '=', false]
            ])
                ->with(['wiki', 'wiki.wikiQueryserviceNamespace'])->get();
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
                $batch = QsBatch::create([
                    'done' => 0,
                    'eventFrom' => $batchesUpToEventId,
                    'eventTo'=> $lastEventId,
                    'wiki_id' => $wikiId,
                    'entityIds' => implode(',', array_unique($entityBatch)),
                    'pending_since' => null,
                ]);
                $newlyCreatedBatches[] = $batch;
            }

            $oldestBatch = collect($newlyCreatedBatches)->merge($notDoneBatches)->sortBy('id')->first();
            if ($oldestBatch === null) {
                return response([]);
            }

            $oldestBatch->update(['pending_since' => Carbon::now()]);
            $oldestBatch->load(['wiki', 'wiki.wikiQueryserviceNamespace']);
            return response([$oldestBatch]);
        });

    }

    public function markBatchesDone(Request $request): \Illuminate\Http\Response
    {
        $batches = (array) $request->json()->get('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 1, 'pending_since' => null]
        );
        return response(1);
    }

    public function markBatchesFailed(Request $request): \Illuminate\Http\Response
    {
        $batches = (array) $request->json()->get('batches');
        QsBatch::whereIn('id', $batches)->increment(
            'processing_attempts', 1,
            ['done' => 0, 'pending_since' => null]
        );
        return response(1);
    }
}
