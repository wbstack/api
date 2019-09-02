<?php

/** @var Router $router */
use Laravel\Lumen\Routing\Router;

// GET
$router->get('wiki/count', ['uses' => 'WikisController@count']);
// POST
$router->post('auth/login', ['uses' => 'Auth\LoginController@login']);
// TODO actually use logout route in VUE app..
$router->post('auth/logout', ['uses' => 'Auth\LoginController@logout']);
$router->post('user/register', ['uses' => 'Auth\RegisterController@register']);
// TODO finish converting for laravel below here
$router->post('user/verifyEmail', ['uses' => 'UserVerificationTokenController@verify']);
$router->post('user/sendVerifyEmail', ['uses' => 'UserVerificationTokenController@createAndSendForUser']);
$router->post('interest/register', ['uses' => 'InterestController@create']);

// Authed
$router->group(['middleware' => ['auth:api']], function () use ($router) {
    // user
    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->post('self', ['uses' => 'UserController@getSelf']);
    });
    // wiki
    $router->group(['prefix' => 'wiki'], function () use ($router) {
        $router->post('create', ['uses' => 'WikiController@create']);
        $router->post('mine', ['uses' => 'WikisController@getWikisOwnedByCurrentUser']);
        $router->post('details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
        // TODO should wiki managers really be here?
        $router->post('managers/list', ['uses' => 'WikiManagersController@getManagersOfWiki']);
    });
    // admin
    $router->group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['admin']], function () use ($router) {
        // invitation
        $router->group(['prefix' => 'invitation'], function () use ($router) {
            $router->post('list', ['uses' => 'InvitationsController@get']);
            $router->post('create', ['uses' => 'InvitationController@create']);
            $router->post('delete', ['uses' => 'InvitationController@delete']);
        });
        // interest
        $router->group(['prefix' => 'interest'], function () use ($router) {
            $router->post('list', ['uses' => 'InterestsController@get']);
            // $router->post('create', ['uses' => 'InvitationController@create']);
        // $router->post('delete', ['uses' => 'InvitationController@delete']);
        });
    });
});
