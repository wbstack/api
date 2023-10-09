<?php

use Illuminate\Support\Facades\Config;

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 * @var \Laravel\Lumen\Routing\Router $router
 */
$router->group(['middleware' => ['throttle:45,1']], function () use ($router) {

    // POST
    $router->post('auth/login', ['uses' => 'Auth\LoginController@login']);
    // TODO actually use logout route in VUE app..
    $router->post('auth/logout', ['uses' => 'Auth\LoginController@logout']);
    $router->post('user/register', [
        'middleware' => ['throttle.signup:'.Config::get('wbstack.signup_throttling_limit').','.Config::get('wbstack.signup_throttling_range')],
        'uses' => 'Auth\RegisterController@register'
    ]);
    $router->post('user/verifyEmail', ['uses' => 'UserVerificationTokenController@verify']);
    $router->post('user/forgotPassword', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
    $router->post('user/resetPassword', ['uses' => 'Auth\ResetPasswordController@reset']);
    $router->post('contact/sendMessage', ['uses' => 'ContactController@sendMessage']);

    $router->apiResource('wiki', 'PublicWikiController')->only(['index', 'show']);
    $router->apiResource('wikiConversionData', 'ConversionMetricController')->only(['getConversionMetric', 'showJson']);

    // Authed
    $router->group(['middleware' => ['auth:api']], function () use ($router) {

        // user
        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->post('self', ['uses' => 'UserController@getSelf']);
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
