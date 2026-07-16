<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PolicyResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'metadata' => [
                'policy_id' => $this->id,
                'type' => $this->policy_type,
                'active_from' => Carbon::parse($this->active_from)->format('Y-m-d'),
                'content_vue_file' => $this->content_vue_file,
            ],
        ];
    }
}
