<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\WikiDomain;

class PublicWikiResource extends JsonResource
{
    public function toArray($request): array
    {
        $logoSetting = $this->settings()->where('name', 'wgLogo')->first();
        return [
            'id' => $this->id,
            'description' => $this->description,
            'domain' => $this->domain,
            'domain_decoded' => $this->domain_decoded,
            'sitename' => $this->sitename,
            'wiki_site_stats' => $this->wikiSiteStats,
            'logo_url' => $logoSetting ? $logoSetting->value : null
        ];
    }
}
