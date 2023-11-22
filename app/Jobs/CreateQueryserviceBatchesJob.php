<?php

namespace App\Jobs;

use App\EventPageUpdate;
use App\QsBatch;
use App\QsCheckpoint;
use Illuminate\Database\Eloquent\Collection;
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
            $latestCheckpoint = QsCheckpoint::get();

            [$newEntities, $latestEventId] = $this->getNewEntities($latestCheckpoint);
            foreach ($newEntities as $wikiId => $entityIdsFromEvents) {
                $success = $this->tryToAppendEntitesToExistingBatches($entityIdsFromEvents, $wikiId);
                if ($success) {
                    continue;
                }
                $this->createNewBatches($entityIdsFromEvents, $wikiId);
            }

            QsCheckpoint::set($latestEventId);
        });
    }

    private function getNewEntities(int $latestCheckpoint): array
    {
        $events = EventPageUpdate::where(
            'id', '>', $latestCheckpoint,
        )
            ->whereIn('namespace', [self::NAMESPACE_ITEM, self::NAMESPACE_PROPERTY, self::NAMESPACE_LEXEME])
            ->get();

        $newEntitiesFromEvents = $events->reduce(function (array $result, EventPageUpdate $event) {
            $result[$event->wiki_id][] = $event->title;
            return $result;
        }, []);

        $latestEventId = $events->reduce(function (int $eventId, EventPageUpdate $event) {
            return $event->id > $eventId ? $event->id : $eventId;
        }, $latestCheckpoint);

        return [$newEntitiesFromEvents, $latestEventId];
    }

    private function tryToAppendEntitesToExistingBatches(array $entityIdsFromEvents, int $wikiId): bool
    {
        $notDoneBatches = QsBatch::where([
            ['done', '=', 0],
            ['pending_since', '=', null],
            ['failed', '=', false],
            ['wiki_id', '=', $wikiId],
        ])->get();

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
            ]);
            return true;
        }
        return false;
    }

    private function createNewBatches(array $entityIdsFromEvents, int $wikiId): void
    {
        $chunks = array_chunk($entityIdsFromEvents, $this->entityLimit);
        foreach ($chunks as $chunk) {
            QsBatch::create([
                'done' => 0,
                'wiki_id' => $wikiId,
                'entityIds' => implode(',', $chunk),
                'pending_since' => null,
            ]);
        }
    }
}
