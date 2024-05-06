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
        $allDeletedWikis = Wiki::OnlyTrashed()->get();
        $output = [];

        foreach ($allDeletedWikis as $wiki) {
            $userId = WikiManager::whereWikiId($wiki->id)->first()['user_id'];

            $output[] = [
                'domain' => $wiki->domain,
                'wiki_deletion_reason' => $wiki->wiki_deletion_reason,
                'number_of_wikibases_for_user' => WikiManager::whereUserId($userId)->count(),
                'number_of_wiki_edits_for_wiki'=> $wiki->wikiSiteStats()->first()['edits'],
                'number_of_entities_for_wiki' => "No value available for now",
                'number_of_wiki_pages_for_wiki' => $wiki->wikiSiteStats()->first()['pages'],
                'number_of_users_for_wiki' => $wiki->wikiSiteStats()->first()['users'],
                'wiki_creation_time' => $wiki->created_at,
                'wiki_deletion_time' => $wiki->deleted_at,
            ];
        }

        if ( $request->wantsJson() ) {
            return response()->json( $output );
        }

        return $this->returnCsv($output);

    }

    private function returnCsv( $output ) {
        ob_start();
        $handle = fopen('php://output', 'r+');
        fputcsv($handle, [
            'domain_name_for_wiki',
            'wiki_deletion_reason',
            'number_of_wikibases_for_user',
            'number_of_wiki_edits_for_wiki',
            'number_of_entities_for_wiki',
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
            'Content-Disposition' => 'attachment;filename='.CarbonImmutable::now()->toIso8601String().'-'.$this->fileName
        ]);
    }
}
