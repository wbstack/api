<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DeletedWikiMetricsController extends Controller
{

    private string $fileName="deleted_wiki_metric.csv";

    /**
     * Produce a downloadable csv file with deleted wiki metrics.
     */
    public function index(Request $request)
    {
        $allDeletedWikis = Wiki::onlyTrashed()->get();
        $output = $this->createOutput($allDeletedWikis);
        return $this->returnCsv($output);
    }

    private function returnCsv( $output ) {
        ob_start();
        $handle = fopen('php://output', 'r+');
        fputcsv($handle, [
            'domain_name_for_wiki',
            'wiki_deletion_reason',
            'number_of_wikibases_owned_by_owners_of_this_wiki',
            'number_of_entities_for_wiki',
            'number_of_wiki_edits_for_wiki',
            'number_of_wiki_pages_for_wiki',
            'number_of_users_for_wiki',
            'wiki_creation_time',
            'wiki_deletion_time',
        ]);
        foreach ($output as $deletedWikiMetrics) {
            fputcsv($handle, array_values($deletedWikiMetrics));
        }
        $csv = ob_get_clean();
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment;filename='.CarbonImmutable::now()->toIso8601String().'-'.$this->fileName ]);
    }

    private function createOutput($wikis): array
    {
        $output = [];
        foreach ($wikis as $wiki) {
            $wikiManagers = $wiki->wikiManagers()->get();
            $usersIds = [];
            foreach($wikiManagers as $wikiManager) {
                $usersIds[] = $wikiManager->pivot->user_id;
            }
            $allWikiIdsOwnedByAllOwners = WikiManager::whereIn('user_id', $usersIds)->pluck('wiki_id')->all();
            $output[] = [
                'domain_name_for_wiki' => $wiki->domain,
                'wiki_deletion_reason' => $wiki->wiki_deletion_reason,
                'number_of_wikibases_owned_by_owners_of_this_wiki' => count(array_unique($allWikiIdsOwnedByAllOwners)),
                'number_of_entities_for_wiki' => "No value available for now",
                'number_of_wiki_pages_for_wiki' => $wiki->wikiSiteStats()->get()['pages'] ?? null,
                'number_of_users_for_wiki' => $wiki->wikiSiteStats()->get()['úsers'] ?? null,
                'wiki_creation_time' => $wiki->created_at,
                'wiki_deletion_time' => $wiki->deleted_at
            ];
        }
        return $output;
    }
}
