<?php

namespace App\Jobs;
use App\WikiLifecycleEvents;

class RemoveDuplicateWikiLifecycleEvents extends Job
{
    public function handle(): void {
        $all = WikiLifecycleEvents::all('wiki_id');

        foreach ($all as $event) {
            $eventsToKeep = WikiLifecycleEvents::where('wiki_id', $event->wiki_id)
                ->latest('id')
                ->first();

            $eventsToDelete = WikiLifecycleEvents::where('wiki_id', $event->wiki_id)
                ->where('id', '<>', $eventsToKeep->id)
                ->get();

            foreach ($eventsToDelete as $eventToDelete) {
                $eventToDelete->delete();
            }
        }
    }
}
