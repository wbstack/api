<?php

namespace App\Jobs;

use App\EventPageUpdate;
use App\QsBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateQueryserviceBatchesJob extends Job
{
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    private const NAMESPACE_LEXEME = 146;

    public function handle(): void
    {
        DB::transaction(function () {
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
                    $event->namespace == self::NAMESPACE_ITEM ||
                    $event->namespace == self::NAMESPACE_PROPERTY ||
                    $event->namespace == self::NAMESPACE_LEXEME
                ) {
                    $wikiBatchesEntities[$event->wiki_id][] = $event->title;
                }
                if ($event->id > $lastEventId) {
                    $lastEventId = $event->id;
                }
            }

            /** @var Collection $newlyCreatedBatches */
            $notDoneBatches = QsBatch::where([
                ['done', '=', 0], ['pending_since', '=', null], ['failed', '=', false]
            ]);
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
                QsBatch::create([
                    'done' => 0,
                    'eventFrom' => $batchesUpToEventId,
                    'eventTo'=> $lastEventId,
                    'wiki_id' => $wikiId,
                    'entityIds' => implode(',', array_unique($entityBatch)),
                    'pending_since' => null,
                ]);
            }
        });
    }
}
