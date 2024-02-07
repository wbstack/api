<?php

namespace App\Jobs;

use App\EventPageUpdate;
use App\QsBatch;
use App\QsCheckpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;

class CreateQueryserviceBatchesJob extends Job
{
    private const NAMESPACE_ITEM = 120;
    private const NAMESPACE_PROPERTY = 122;
    private const NAMESPACE_LEXEME = 146;

    private int $entityLimit;

    public $timeout = 3600;

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
                try {
                    $success = $this->tryToAppendEntitesToExistingBatches($entityIdsFromEvents, $wikiId);
                    if ($success) {
                        continue;
                    }
                    $this->createNewBatches($entityIdsFromEvents, $wikiId);
                } catch (\Exception $ex) {
                    Log::error(
                        'Failed to process entities '.implode(',', $entityIdsFromEvents).' for wiki with id '.$wikiId.': '.$ex->getMessage()
                    );
                    $this->fail($ex);
                }
            }

            QsCheckpoint::set($latestEventId);
        }, 3);
    }

    private function getNewEntities(int $latestCheckpoint): array
    {
        $newEntitiesFromEvents = [];
        $latestEventId = $latestCheckpoint;

        EventPageUpdate::where(
            'id', '>', $latestCheckpoint,
        )
            ->whereIn('namespace', [self::NAMESPACE_ITEM, self::NAMESPACE_PROPERTY, self::NAMESPACE_LEXEME])
            ->has('wiki')
            ->chunk(100, function (Collection $chunk) use (&$newEntitiesFromEvents, &$latestEventId) {
                foreach ($chunk as $event) {
                    $newEntitiesFromEvents[$event->wiki_id][] = $event->title;
                    $latestEventId = max($event->id, $latestEventId);
                }
            });

        return [$newEntitiesFromEvents, $latestEventId];
    }

    private function tryToAppendEntitesToExistingBatches(array $entityIdsFromEvents, int $wikiId): bool
    {
        if (DB::transactionLevel() < 1) {
            throw new \Exception(
                'This method can only be run within the context of a transaction.'
            );
        }

        $notDoneBatches = QsBatch::where([
            ['done', '=', 0],
            ['pending_since', '=', null],
            ['failed', '=', false],
            ['wiki_id', '=', $wikiId],
        ])
            ->lockForUpdate()
            ->get();

        foreach ($notDoneBatches as $qsBatch) {
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
        $chunks = array_chunk(
            array_unique($entityIdsFromEvents), $this->entityLimit,
        );
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
