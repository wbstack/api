<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PoliciesCollection extends ResourceCollection {
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array {
        return ['items' => $this->collection->map(function ($policy) {
            return new PolicyResource($policy);
        }), ];
    }
}
