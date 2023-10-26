<?php

namespace App\Http\Controllers;

use App\Wiki;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class ConversionMetricController extends Controller
{

    private string $fileName="conversion_metric_for_all_wikis.csv";

    /**
     * Produce a downloadable csv file with conversion metrics for all wikis.
     */
    public function index(Request $request)
    {
        $allWikis = Wiki::all();
        $current_date = CarbonImmutable::now();
        $output = [];
        

        foreach ($allWikis as $wiki) {
            $lifecycleEvents = $wiki->wikiLifecycleEvents()->first();
            $wikiLastEditedTime = null;
            $wikiFirstEditedTime = null;
            if (!empty($lifecycleEvents)  ) {
                if ($lifecycleEvents['last_edited']) {
                    $wikiLastEditedTime = CarbonImmutable::parse($lifecycleEvents['last_edited']);    
                }
                if ($lifecycleEvents['first_edited']) {
                    $wikiFirstEditedTime = CarbonImmutable::parse($lifecycleEvents['first_edited']);
                }
            }
            $time_before_wiki_abandoned_days = null;
            $time_to_engage_days = null;

            if (!is_null($wikiLastEditedTime) && ($wikiLastEditedTime->diffInDays($current_date, false) >= 90)) {
                $time_before_wiki_abandoned_days = $wiki->created_at->diffInDays($wikiLastEditedTime, false);
            }
            if ($wikiFirstEditedTime !== null) {
                $time_to_engage_days = $wiki->created_at->diffInDays($wikiFirstEditedTime, false);
            }
            $wiki_number_of_editors = $wiki->wikiSiteStats()->first()['activeusers'] ?? null;

            $output[] = [
                'domain' => $wiki->domain,
                'time_to_engage_days' => $time_to_engage_days,
                'time_before_wiki_abandoned_days' => $time_before_wiki_abandoned_days,
                'number_of_active_editors' => $wiki_number_of_editors
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
        fputcsv($handle, ['domain_name', 'time_to_engage_days', 'time_before_wiki_abandoned_days', 'number_of_active_editors']);
        foreach ($output as $wikiMetrics) {
            fputcsv($handle, array_values($wikiMetrics));
        }
        $csv = ob_get_clean();
        return response($csv, 200, [
			'Content-Type' => 'text/csv',
			'Content-Disposition' => 'attachment;filename='.CarbonImmutable::now()->toIso8601String().'-'.$this->fileName
		]);;
    }
}
