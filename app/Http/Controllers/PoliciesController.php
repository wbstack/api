<?php

namespace App\Http\Controllers;

use App\Http\Resources\PoliciesCollection;
use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class PoliciesController extends Controller {
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

    private function activePolicyIdsQuery(CarbonImmutable $now): Builder {
        return Policy::query()
            ->selectRaw('MAX(id) as id')
            ->where('active_from', '<=', $now)
            ->groupBy('policy_type')
            ->toBase();
    }
}
