<?php

namespace App\Http\Controllers;

use App\Http\Resources\PoliciesCollection;
use App\Policy;
use App\PolicyAcceptance;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PoliciesController extends Controller {
    public function getCurrentPolicies(): PoliciesCollection {
        $now = CarbonImmutable::now();

        // This works based on the assumption that the latest policy has the highest id given that id is AUTO_INCREMENT
        $latestPolicyIds = Policy::where('active_from', '<', $now)
            ->selectRaw('MAX(id) as id')
            ->groupBy('policy_type')
            ->pluck('id');

        $currentPolicies = Policy::whereIn('id', $latestPolicyIds)->get();

        return new PoliciesCollection($currentPolicies);
    }

    public function getMissingPolicies(Request $request): PoliciesCollection {
        $now = CarbonImmutable::now();

        // This works based on the assumption that the latest policy has the highest id given that id is AUTO_INCREMENT
        $latestPolicyIds = Policy::where('active_from', '<', $now)
            ->selectRaw('MAX(id) as id')
            ->groupBy('policy_type')
            ->pluck('id');

        $acceptedPolicyIds = PolicyAcceptance::where('user_id', $request->user()->id)
            ->whereIn('policy_id', $latestPolicyIds)
            ->pluck('policy_id');

        $missingPolicies = Policy::whereIn('id', $latestPolicyIds)
            ->whereNotIn('id', $acceptedPolicyIds)
            ->get();

        return new PoliciesCollection($missingPolicies);
    }
}
