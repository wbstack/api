<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PoliciesCollection extends ResourceCollection {
    // per default Laravel wraps ResourceCollections in a `data` key: https://laravel.com/docs/11.x/eloquent-resources#data-wrapping
    // which is not wanted in this case: https://phabricator.wikimedia.org/T429591
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): Collection {
        return $this->collection;
    }
}
