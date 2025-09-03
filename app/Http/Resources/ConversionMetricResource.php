<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversionMetricResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'domain' => $this->domain,
            'sitename' => $this->sitename,
            'wiki_site_stats' => $this->wikiSiteStats,
            'wiki_lifecycle_event' => $this->wikiLifecycleEvents,
        ];
    }
}
