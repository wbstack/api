<?php

use Illuminate\Support\Facades\Config;

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 * @var Illuminate\Routing\Router $router
 */
$router->group(['middleware' => ['throttle:45,1']], function () use ($router) {
    // TODO actually use logout route in VUE app..
    $router->post('user/register', [
        'middleware' => ['throttle.signup:'.Config::get('wbstack.signup_throttling_limit').','.Config::get('wbstack.signup_throttling_range')],
        'uses' => 'Auth\RegisterController@register'
    ]);
    $router->post('user/verifyEmail', ['uses' => 'UserVerificationTokenController@verify']);
    $router->post('user/forgotPassword', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
    $router->post('user/resetPassword', ['uses' => 'Auth\ResetPasswordController@reset']);
    $router->post('contact/sendMessage', ['uses' => 'ContactController@sendMessage']);

    $router->apiResource('wiki', 'PublicWikiController')->only(['index', 'show']);
    $router->apiResource('wikiConversionData', 'ConversionMetricController')->only(['index']);
    $router->apiResource('deletedWikiMetrics', 'DeletedWikiMetricsController')->only(['index'])
        ->middleware(AuthorisedUsersForDeletedWikiMetricsMiddleware::class);

    $router->post('auth/login', ['uses' => 'Auth\LoginController@postLogin'])->name('login');
    // Authed
    $router->group(['middleware' => ['auth:api']], function () use ($router) {
        $router->get('auth/login', ['uses' => 'Auth\LoginController@getLogin']);
        $router->delete('auth/login', ['uses' => 'Auth\LoginController@deleteLogin']);

        // user
        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->post('sendVerifyEmail', ['uses' => 'UserVerificationTokenController@createAndSendForUser']);
        });

        // wiki
        // TODO wiki id should probably be in the path of most of these routes...
        $router->group(['prefix' => 'wiki'], function () use ($router) {
            // TODO maybe the UI just shouldn't make this request if users are not verified...
            $router->post('mine', ['uses' => 'WikisController@getWikisOwnedByCurrentUser']);
        });
        $router->group(['prefix' => 'wiki', 'middleware' => ['verified']], function () use ($router) {
            $router->post('create', ['uses' => 'WikiController@create']);
            $router->post('delete', ['uses' => 'WikiController@delete']);
            $router->post('details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
            $router->post('logo/update', ['uses' => 'WikiLogoController@update']);
            $router->post('setting/{setting}/update', ['uses' => 'WikiSettingController@update']);
            // TODO should wiki managers really be here?
            $router->post('managers/list', ['uses' => 'WikiManagersController@getManagersOfWiki']);
        });
    });
});
