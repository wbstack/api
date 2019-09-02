<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // SHIFT NOTE: Shift added this, and it was not here below
        $this->registerPolicies();

        //SHIFT NOTE: Code below is form Pre shift..
        $this->app['auth']->viaRequest('api', function ($request) {
            $token = $request->get('token') ?: $request->header('Authorization');

            if (! $token) {
                // Unauthorized response if token not there
                return;
            }

            try {
                $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
            } catch (ExpiredException $e) {
                return;
            } catch (Exception $e) {
                return;
            }

            return User::find($credentials->sub);
        });
    }
}
