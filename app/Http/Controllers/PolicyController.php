<?php

namespace App\Http\Controllers;

use App\Http\Resources\PolicyResource;
use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PolicyController extends Controller {
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
