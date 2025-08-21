<?php

namespace App\Http\Middleware;

use App\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class ThrottleSignup {
    public function handle(Request $request, Closure $next, string $limit, string $range) {
        if (!$limit || !$range) {
            return $next($request);
        }

        $lookback = Carbon::now()->sub(new \DateInterval($range));
        $recentSignups = User::where('created_at', '>=', $lookback)->count();
        if ($recentSignups >= intval($limit)) {
            report('Attempted account creation was blocked because given limits were exceeded.');

            return response()->json(
                ['errors' => "Due to high load, we're currently not able to create an account for you. Please try again tomorrow or reach out through our contact page."],
                503
            );
        }

        return $next($request);
    }
}
