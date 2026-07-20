<?php

namespace App\Http\Controllers;

use App\Http\Resources\PoliciesCollection;
use App\Http\Resources\PolicyResource;
use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

    public function getPolicyByTypeAndActiveFrom($policyType, $activeFrom): PolicyResource {
        $validator = Validator::make(
            [
                'policy_type' => $policyType,
                'active_from' => $activeFrom,
            ],
            [
                'policy_type' => ['required', 'string', Rule::in(['terms-of-use', 'hosting-policy'])],
                'active_from' => ['required', 'date', 'date_format:Y-m-d'],
            ]

        );
        $validator->validate();
        $validated = $validator->safe();

        $validatedActiveFrom = CarbonImmutable::parse($validated['active_from']);

        $policy = Policy::where('policy_type', $validated['policy_type'])->where('active_from', '=', $validatedActiveFrom)->first();

        if (!$policy) {
            abort(404, 'Policy not found.');
        }

        return new PolicyResource($policy);
    }
}
