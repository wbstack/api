<?php

namespace App\Http\Controllers;

use App\Wiki;
use Carbon\Carbon;
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
        $current_date = Carbon::now();
        $output = [];
        

        foreach ($allWikis as $wiki) {
            $lifecycleEvents = $wiki->wikiLifecycleEvents()->first();
            $wikiLastEditedTime = Carbon::parse($lifecycleEvents['last_edited'] ?? null);
            $wikiFirstEditedTime = Carbon::parse($lifecycleEvents['first_edited'] ?? null);
            $time_before_wiki_abandoned_days = null;
            $time_to_engage_days = null;

            if (!is_null($wikiLastEditedTime) && ($current_date->diffInDays($wikiLastEditedTime) >= 90)) {
                $time_before_wiki_abandoned_days = $wikiLastEditedTime->diffInDays($wiki->created_at);
            }
            if ($wikiFirstEditedTime !== null) {
                $time_to_engage_days = $wikiFirstEditedTime->diffInDays($wiki->created_at);
            }
            $wiki_number_of_editors = $wiki->wikiSiteStats()->first()['activeusers'] ?? null;

            $output[] = [$wiki->domain, $time_to_engage_days, $time_before_wiki_abandoned_days, $wiki_number_of_editors];

        }

        ob_start();
		$handle = fopen('php://output', 'r+');
        fputcsv($handle, ['domain_name', 'time_to_engage_days', 'time_before_wiki_abandoned_days', 'number_of_active_editors']);
        foreach ($output as $wikiMetrics) {
            fputcsv($handle, array_values($wikiMetrics));
        }
        $csv = $csv = ob_get_clean();
        return response($csv, 200, [
			'Content-Type' => 'text/csv',
			'Content-Disposition' => 'attachment;filename='.$this->fileName
		]);;

    }
}
