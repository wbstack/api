<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WikiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'domain' => $this->domain,
            'sitename' => $this->sitename,
            'profile' => $this->whenLoaded('latestProfile', function () {
                if (!$this->latestProfile) {
                    return null;
                }
                return [
                    'id' => $this->latestProfile->id,
                    'wiki_id' => $this->latestProfile->wiki_id,
                    'purpose' => $this->latestProfile->purpose,
                    'purpose_other' => $this->latestProfile->purpose_other,
                    'audience' => $this->latestProfile->audience,
                    'audience_other' => $this->latestProfile->audience_other,
                    'temporality' => $this->latestProfile->temporality,
                    'temporality_other' => $this->latestProfile->temporality_other
                ];
            })
        ];
    }
}
