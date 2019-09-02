<?php

namespace App\Providers;

use App\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
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
