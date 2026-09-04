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

    public function getCurrentPolicyByType($policyType): PolicyResource {
        $validator = Validator::make(
            [
                'policy_type' => $policyType,
            ],
            [
                'policy_type' => ['required', 'string', Rule::in(['terms-of-use', 'hosting-policy'])],
            ]
        );
        $validator->validate();
        $validated = $validator->safe();

        $policy = Policy::where('policy_type', $validated['policy_type'])
            ->where('active_from', '<=', today())
            ->latest('active_from')
            ->orderByDesc('id')
            ->first();

        if (!$policy) {
            abort(404, 'Policy not found.');
        }

        return new PolicyResource($policy);
    }

    public function getUpcomingPolicyByType($policyType): PolicyResource {
        $validator = Validator::make(
            [
                'policy_type' => $policyType,
            ],
            [
                'policy_type' => ['required', 'string', Rule::in(['terms-of-use', 'hosting-policy'])],
            ]
        );
        $validator->validate();
        $validated = $validator->safe();

        $policies = Policy::where('policy_type', $validated['policy_type'])
            ->where(function ($query) {
                $query->where('active_from', '>', today())
                    ->orWhereNull('active_from');
            })
            ->orderBy('active_from')
            ->get();

        if ($policies->count() < 1) {
            abort(404, 'Policy not found.');
        }

        if ($policies->count() > 1) {
            abort(500, 'Multiple policies found.');
        }

        return new PolicyResource($policies->first());
    }
}
