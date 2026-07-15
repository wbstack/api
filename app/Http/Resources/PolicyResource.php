<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return ['metadata' => [
            'policy_id' => $this->id,
            'type' => $this->type,
            'active_from' => $this->active_from,
            'content_vue_file' => $this->content_vue_file,
        ],
        ];
    }
}
