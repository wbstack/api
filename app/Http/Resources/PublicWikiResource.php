<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicWikiResource extends JsonResource {
    public function toArray($request): array {
        $logoSetting = $this->settings()->where('name', 'wgLogo')->first();

        return [
            'id' => $this->id,
            'description' => $this->description,
            'domain' => $this->domain,
            'domain_decoded' => $this->domain_decoded,
            'sitename' => $this->sitename,
            'wiki_site_stats' => $this->wikiSiteStats,
            'logo_url' => $logoSetting ? $logoSetting->value : null,

            // TODO: delete these three fields before merging; here to easily prove the `reuse_prototype` logic works
            'test_purpose' => $this->wikiLatestProfile ? $this->wikiLatestProfile->purpose : null,
            'test_temporality' => $this->wikiLatestProfile ? $this->wikiLatestProfile->temporality : null,
            'test_audience' => $this->wikiLatestProfile ? $this->wikiLatestProfile->audience : null,

            // Checking relation load state before reading it to avoid N+1 query
            // This relies on the controller to eager load `wikiLatestProfile` relationship
            'reuse_prototype' => $this->relationLoaded('wikiLatestProfile')
                ? ($this->wikiLatestProfile
                    ? $this->wikiLatestProfile->purpose === 'data_hub'
                    && $this->wikiLatestProfile->temporality === 'permanent'
                    && $this->wikiLatestProfile->audience === 'wide'
                    : null)
                : null,
        ];
    }
}
