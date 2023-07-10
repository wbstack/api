<?php
 
namespace App\Http\Middleware;
 
use Closure;
use Carbon\Carbon;
use App\User;
use Illuminate\Http\Request;
 
class ThrottleSignup
{
    public function handle(Request $request, Closure $next, string $limit, string $range)
    {
        $lookback = Carbon::now()->sub(new \DateInterval($range));
        $recentSignups = User::where('created_at', '>=', $lookback)->count();
        if ($recentSignups >= intval($limit)) {
            return response()->json(
                ["error" => "Due to high load, we're currently not able to create an account for you. Please try again tomorrow or reach out through our contact page."],
                429
            );
        }
 
        return $next($request);
    }
}
