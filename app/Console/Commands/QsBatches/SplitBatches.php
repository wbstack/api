<?php

namespace App\Console\Commands\QsBatches;

use App\QsBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class SplitBatches extends Command
{
    protected $signature = 'wbs-qs-batches:split';

    protected $description = 'Split existing batches into correctly sized entities';

    private int $entityLimit;

    public function __construct()
    {
        parent::__construct();
        $this->entityLimit = Config::get('wbstack.qs_batch_entity_limit');
    }
    public function handle(): int
    {
        return DB::transaction(function () {
            $exitCode = 0;

            QsBatch::where([
                ['done', '=', 0],
                ['pending_since', '=', null],
                ['failed', '=', false]
            ])
                ->get()
                ->each(function ($batch) use (&$exitCode) {
                    try {
                        $entityIds = explode(',', $batch->entityIds);
                        if (count($entityIds) <= $this->entityLimit) {
                            return;
                        }
                        $chunks = array_chunk($entityIds, $this->entityLimit);

                        foreach ($chunks as $idx => $chunk) {
                            if ($idx === 0) {
                                $batch->update(['entityIds' => implode(',', $chunk)]);
                            } else {
                                QsBatch::create([
                                    'done' => 0,
                                    'eventFrom' => $batch->eventFrom,
                                    'eventTo'=> $batch->eventTo,
                                    'wiki_id' => $batch->wiki_id,
                                    'entityIds' => implode(',', $chunk),
                                    'pending_since' => null,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error($e->getMessage());
                        $exitCode = 1;
                    }
                });

            return $exitCode;
        });
    }
}
