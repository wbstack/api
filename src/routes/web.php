<?php

/** @var Router $router */

use Laravel\Lumen\Routing\Router;

// TODO use namespaces?
// TODO use route prefixes?

// Public
$router->group(['middleware' => ['cors']], function () use ($router) {
    // GET
    $router->get('wiki/count', ['uses' => 'WikisController@count']);
    // POST
    $router->post('auth/login', ['uses' => 'AuthController@authenticate']);
    $router->post('user/register', ['uses' => 'UserController@create']);
    $router->post('interest/register', ['uses' => 'InterestController@create']);
});

// Authed
$router->group(['middleware' => ['cors', 'jwt.auth']], function () use ($router) {
    // POST
    $router->post('user/self', ['uses' => 'UserController@getSelf']);
    $router->post('wiki/create', ['uses' => 'WikiController@create']);
    $router->post('invitation/list', ['uses' => 'InvitationsController@get']);
    $router->post('invitation/create', ['uses' => 'InvitationController@create']);
    $router->post('invitation/delete', ['uses' => 'InvitationController@delete']);
    $router->post('wiki/mine', ['uses' => 'WikisController@getWikisOwnedByCurrentUser']);
    $router->post('wiki/details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
    $router->post('wiki/managers/list', ['uses' => 'WikiManagersController@getManagersOfWiki']);
});

// Backend Only
$router->group(['middleware' => ['backend.auth']], function () use ($router) {
    // GET
    $router->get('wiki/database/countUnclaimed', ['uses' => 'WikiDbsController@countUnclaimed']);
    $router->get('wiki/getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
    // POST
    $router->post('wiki/database/recordCreation', ['uses' => 'WikiDbController@create']);
});

// Allow options methods on all routes?
// TODO do I really want this to be all routes?
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);
