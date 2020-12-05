<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class BackendAuth
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header('X-Backend-Token');
        $service = $request->header('X-Backend-Service');

        // TODO restrict on IP range

        // TODO configure service and token list via env vars?
        // TODO actually match service -> token
        // TODO allow multiple tokens per service to be configured?
        $tokenOk = $token === 'backend-token';
        $serviceOk = $service === 'backend-service';

        if (! $tokenOk || ! $serviceOk) {
            // TODO log failures
            return response()->json([
                'error' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
