<?php

namespace App\Metrics\App;

use App\Wiki;
use App\WikiDailyMetrics;
use Illuminate\Support\Facades\Http;

class WikiMetrics
{

    public function saveMetrics(Wiki $wiki): void
    {
        $today = now()->format('Y-m-d');
        $oldRecord = WikiDailyMetrics::where('wiki_id', $wiki->id)->latest('date')->first();
        $todayPageCount = $wiki->wikiSiteStats()->first()->pages ?? 0;
        $isDeleted = (bool)$wiki->deleted_at;
        $tripleCount = null;
        if (!$isDeleted) {
            $tripleCount = $this->getNumOfTriples($wiki);
        }

        if ($oldRecord) {
            if ($oldRecord->is_deleted) {
                \Log::info("Wiki is deleted, no new record for Wiki ID {$wiki->id}.");
                return;
            }
            if (!$isDeleted) {
                if ($oldRecord->pages === $todayPageCount) {
                    \Log::info("Page count unchanged for Wiki ID {$wiki->id}, no new record added.");
                    if ($oldRecord->number_of_triples === $tripleCount) {
                        \Log::info("Number of tripples unchanged for Wiki ID {$wiki->id}, no new record added.");
                        return;
                    }
                }
            }
        }
        WikiDailyMetrics::create([
            'id' => $wiki->id . '_' . date('Y-m-d'),
            'pages' => $todayPageCount,
            'is_deleted' => $isDeleted,
            'date' => $today,
            'wiki_id' => $wiki->id,
            'number_of_triples' => $tripleCount,
        ]);
        \Log::info("New metric recorded for Wiki ID {$wiki->id}");
    }
    private function getNumOfTriples(Wiki $wiki): ?int
    {
        $endpoint = 'https://'. $wiki->domain .'/query/sparql';

        $query = 'SELECT (COUNT(*) AS ?triples) WHERE { ?s ?p ?o }';

        $response = Http::withHeaders([
            'Accept' => 'application/sparql-results+json'
        ])->get($endpoint, [
            'query' => $query
        ]);
        if ($response->successful()) {
            $data = $response->json();
            return $data['results']['bindings'][0]['triples']['value'];
        }
        return null;
    }

}

