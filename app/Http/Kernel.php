<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BackendAuth;
use App\Http\Middleware\LimitWikiAccess;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\Throttle;
use App\Http\Middleware\ThrottleSignup;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel {
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $middlewareAliases = [
        // Middleware from laravel upstream
        'verified' => EnsureEmailIsVerified::class,

        // Custom Middleware
        'guest' => RedirectIfAuthenticated::class,
        'limit_wiki_access' => LimitWikiAccess::class,
        'backend.auth' => BackendAuth::class,
        'throttle.signup' => ThrottleSignup::class,
        'throttle' => Throttle::class,
        'auth' => Authenticate::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        StartSession::class,
        ShareErrorsFromSession::class,
        Authenticate::class,
        AuthenticateSession::class,
        SubstituteBindings::class,
        Authorize::class,
    ];
}
