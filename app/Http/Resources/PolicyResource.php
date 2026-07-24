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
    public static $wrap = null;

    public function toArray(Request $request): array {
        $activeFrom = $this->active_from;

        if ($this->active_from !== null) {
            $activeFrom = Carbon::parse($this->active_from)->format('Y-m-d');
        }

        return [
            'metadata' => [
                'policy_id' => $this->id,
                'type' => $this->policy_type,
                'active_from' => $activeFrom,
                'content_vue_file' => $this->content_vue_file,
            ],
        ];
    }
}
