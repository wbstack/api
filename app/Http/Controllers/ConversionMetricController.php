<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversionMetricResource;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConversionMetricController extends Controller
{
    /**
     * Produce a downloadable csv file with conversion metrics for all wikis.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function index(Request $request)
    {
        $allWikis = Wiki::all();
        $current_date = Carbon::now();
        $csv_file = fopen('php://output', 'w');
        fputcsv($csv_file, ['domain_name', 'time_to_engage_days', 'time_since_wiki_abandoned_days', 'number_of_active_editors']);

        foreach ($allWikis as $wiki) {
            $wikiLastEditedTime = Carbon::parse(($wiki->wikiLifecycleEvents()->get('last_edited')[0])->last_edited);
            $wikiFirstEditedTime = Carbon::parse(($wiki->wikiLifecycleEvents()->get('first_edited')[0])->first_edited);
            $wiki_time_to_abandon_days = null;
            $time_to_engage_days = null;

            if (!is_null($wikiLastEditedTime) && ($current_date->diffInDays($wikiLastEditedTime) >= 90)) {
                $wiki_time_to_abandon_days = $wikiLastEditedTime->diffInDays($wiki->created_at);
            }
            if ($wikiFirstEditedTime !== null) {
                $time_to_engage_days = $wikiFirstEditedTime->diffInDays($wiki->created_at);
            }
            $wiki_number_of_editors = $wiki->wikiSiteStats()->get('activeusers')[0]->activeusers;

            fputcsv($csv_file, [$wiki->domain, $wiki_time_to_abandon_days, $time_to_engage_days, $wiki_number_of_editors]);

        }
        return response()->streamDownload(function () {
            flush();
        }, 'conversion_metric_for_all_wikis.txt', ['Content-Type' => 'text/csv']);

    }
}
