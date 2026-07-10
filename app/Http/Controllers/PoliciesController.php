<?php

namespace App\Http\Controllers;

use App\Http\Resources\PoliciesCollection;
use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PoliciesController extends Controller {

    public function getCurrentPolicies() {
        $now = CarbonImmutable::now();

        // This works based on the assumption that the latest policy has the highest id given that id is AUTO_INCREMENT
        $latestPolicyIds = Policy::where('active_from', '<=', $now)
            ->selectRaw('MAX(id) as id')
            ->groupBy('policy_type')
            ->pluck('id');

        $currentPolicies = Policy::whereIn('id', $latestPolicyIds)->get();
        return new PoliciesCollection($currentPolicies);
    }
}
