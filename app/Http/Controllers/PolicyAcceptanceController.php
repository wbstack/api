<?php

namespace App\Http\Controllers;

use App\Policy;
use App\PolicyAcceptance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolicyAcceptanceController extends Controller {
    public function store(Request $request): JsonResponse {
        $request->validate([
            'policy_ids' => ['required', 'array'],
            'policy_ids.*' => ['integer'],
        ]);

        $policyIds = $request->input('policy_ids');

        // Check all policy IDs exist before writing anything
        $existingIds = Policy::whereIn('id', $policyIds)->pluck('id')->all();
        $missingIds = array_values(array_diff($policyIds, $existingIds));

        if (!empty($missingIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some policy IDs do not exist.',
                'data' => ['missing_policy_ids' => $missingIds],
            ], 400);
        }

        $userId = $request->user()->id;

        foreach ($policyIds as $policyId) {
            // Ignore if the user has already accepted this policy
            PolicyAcceptance::firstOrCreate(
                ['user_id' => $userId, 'policy_id' => $policyId],
                ['accepted_at' => now()],
            );
        }

        return response()->json(['success' => true]);
    }
}
