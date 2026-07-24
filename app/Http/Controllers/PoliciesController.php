<?php

namespace App\Http\Controllers;

use App\Http\Resources\PoliciesCollection;
use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PoliciesController extends Controller {
    private function activePolicyIdsQuery(CarbonImmutable $now): Builder {
        return Policy::query()
            ->selectRaw('MAX(id) as id')
            ->where('active_from', '<=', $now)
            ->groupBy('policy_type')
            ->toBase();
    }

    public function getCurrentPolicies(): PoliciesCollection {
        $now = CarbonImmutable::now();

        $currentPolicies = Policy::whereIn('id', $this->activePolicyIdsQuery($now))->get();

        return new PoliciesCollection($currentPolicies);
    }

    public function getMissingPolicies(Request $request): PoliciesCollection {
        $now = CarbonImmutable::now();

        $missingPolicies = Policy::whereIn('id', $this->activePolicyIdsQuery($now))
            ->whereNotExists(function (Builder $query) use ($request): void {
                $query->selectRaw('1')
                    ->from('policy_acceptances')
                    ->whereColumn('policy_acceptances.policy_id', 'policies.id')
                    ->where('policy_acceptances.user_id', $request->user()->id);
            })->get();

        return new PoliciesCollection($missingPolicies);
    }

    public function getPoliciesByType($policyType): PoliciesCollection {
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

        $policies = Policy::where('policy_type', $validated['policy_type'])->get();

        return new PoliciesCollection($policies);
    }
}
