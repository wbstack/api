<?php

namespace App\Jobs;

use App\EventPageUpdate;
use App\QsBatch;
use App\QsCheckpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CreateQueryserviceBatchesJob extends Job
{
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    private const NAMESPACE_LEXEME = 146;

    private int $entityLimit;

    public function __construct()
    {
        $this->entityLimit = Config::get('wbstack.qs_batch_entity_limit');
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $lastCheckpoint = QsCheckpoint::get();

            // Get events after the point that batch was created
            // TODO maybe filter by NS here?
            $events = EventPageUpdate::where(
                'id', '>', $lastCheckpoint
            )->get();

            $wikiBatchesEntities = [];
            $latestEventId = $lastCheckpoint;
            foreach ($events as $event) {
                if (
                    $event->namespace == self::NAMESPACE_ITEM ||
                    $event->namespace == self::NAMESPACE_PROPERTY ||
                    $event->namespace == self::NAMESPACE_LEXEME
                ) {
                    $wikiBatchesEntities[$event->wiki_id][] = $event->title;
                }
                if ($event->id > $latestEventId) {
                    $latestEventId = $event->id;
                }
            }

            $notDoneBatches = QsBatch::where([
                ['done', '=', 0],
                ['pending_since', '=', null],
                ['failed', '=', false],
            ])->get();

            // Insert the newly created batches into the table...
            foreach ($wikiBatchesEntities as $wikiId => $entityIdsFromEvents) {
                // If we already have a not done batch for this same wiki, then merge that into a new batch
                foreach ($notDoneBatches as $qsBatch) {
                    if ($qsBatch->wiki_id !== $wikiId) {
                        continue;
                    }

                    $entitiesOnBatch = explode(',', $qsBatch->entityIds);
                    $tentativeMerge = array_unique(array_merge($entityIdsFromEvents, $entitiesOnBatch));
                    if (count($tentativeMerge) > $this->entityLimit) {
                        continue;
                    }

                    $qsBatch->update([
                        'entityIds' => implode(',', $tentativeMerge),
                        'eventFrom' => $lastCheckpoint,
                        'eventTo'=> $latestEventId,
                    ]);
                    // after updating, we need to skip the creation below
                    // so we continue the outer loop instead
                    continue 2;
                }

                $chunks = array_chunk($entityIdsFromEvents, $this->entityLimit);
                foreach ($chunks as $chunk) {
                    // Insert the new batch
                    QsBatch::create([
                        'done' => 0,
                        'eventFrom' => $lastCheckpoint,
                        'eventTo'=> $latestEventId,
                        'wiki_id' => $wikiId,
                        'entityIds' => implode(',', $chunk),
                        'pending_since' => null,
                    ]);
                }
            }

            QsCheckpoint::set($latestEventId);
        });
    }
}
