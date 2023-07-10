<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThrottleSignup
{
    public function handle(Request $request, Closure $next, string $limit, string $range)
    {
        if (!$limit || !$range) {
            return $next($request);
        }

        $lookback = Carbon::now()->sub(new \DateInterval($range));
        $recentSignups = User::where('created_at', '>=', $lookback)->count();
        if ($recentSignups >= intval($limit)) {
            Log::error("WARN_SIGNUP_THROTTLED: Given limit of '$limit' in range '$range' was exceeded, attempted account creation was blocked.");
            return response()->json(
                ["error" => "Due to high load, we're currently not able to create an account for you. Please try again tomorrow or reach out through our contact page."],
                429
            );
        }

        return $next($request);
    }
}
