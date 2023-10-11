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
    public function getConversionMetric(Request $request)
    {
        $allWikis = Wiki::all();
        $current_date = Carbon::now();
        $csv_file = fopen('php://output', 'w');
        fputcsv($csv_file, ['domain_name','time_to_engage_days','time_since_wiki_abandoned_days','number_of_active_editors']);


        foreach($allWikis as $wiki){
            $wikiLifecycleEvent = $wiki->wikiLifecycleEvents()->get();

            $wiki_time_to_abandon_days= $current_date->diffInDays(Carbon::parse($wikiLifecycleEvent->get('last_edited')));
            $time_to_engage_days= Carbon::parse($wikiLifecycleEvent->get('first_edited'))->diffInDays($wiki->created_at);
            $wiki_number_of_editors = $wiki->wikiSiteStats()->get('activeusers');

            fputcsv($csv_file, [$wiki->domain, $wiki_time_to_abandon_days, $time_to_engage_days, $wiki_number_of_editors]);

        }
        return response()->streamDownload(function ($csv_file) {
            echo $csv_file->getContent();
            flush();
        }, 'conversion_metric_for_all_wikis.txt', ['Content-Type' => 'text/csv']);

    }

    /**
     * Display the output in json.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showJson(){
        return ConversionMetricResource::collection(Wiki::query());
    }
}
