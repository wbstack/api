<?php

/** @var Router $router */

use Laravel\Lumen\Routing\Router;

$wwRoutes = [
    'front' => [
        'GET' => [
            'noauth' => [
                'wiki/count' => 'WikisController@count',
            ],
            'auth' => [
            ],
        ],
        'POST' => [
            'auth' => [
                'user/self' => 'UserController@getSelf',
                'wiki/create' => 'WikiController@create',
                'invitation/list' => 'InvitationsController@get',
                'invitation/create' => 'InvitationController@create',
                'invitation/delete' => 'InvitationController@delete',
                'wiki/mine' => 'WikisController@getWikisOwnedByCurrentUser',
                'wiki/details' => 'WikiController@getWikiDetailsForIdForOwner',
                'wiki/managers/list' => 'WikiManagersController@getManagersOfWiki',
            ],
            'noauth' => [
                'auth/login' => 'AuthController@authenticate',
                'user/register' => 'UserController@create',
                'interest/register' => 'InterestController@create',
            ],
        ],
    ],
    'back' => [
        'GET' => [
            'auth' => [
                'wiki/database/countUnclaimed' => 'WikiDbsController@countUnclaimed',
                'wiki/getWikiForDomain' => 'WikiController@getWikiForDomain',
            ],
        ],
        'POST' => [
            'auth' => [
                'wiki/database/recordCreation' => 'WikiDbController@create',
            ],
        ],
    ],
];

// TODO use route groups?
// TODO use namespaces?
// TODO use route prefixes?

// TODO only register backend routes when a request is coming internally?
foreach ( $wwRoutes as $frontOrback => $methods ) {
    foreach ( $methods as $method => $auths ) {
        foreach ( $auths as $authOrNoAuth => $routes ) {
            foreach ( $routes as $uri => $controller ) {
                $action = [
                    'uses' => $controller,
                    'middleware' => [],
                ];
                if ( $frontOrback === 'back' ) {
                    $action['middleware'][] = 'backend.auth';
                }
                if ( $frontOrback === 'front' ) {
                    $action['middleware'][] = 'cors';
                }
                if ( $frontOrback === 'front' && $authOrNoAuth === 'auth' ) {
                    $action['middleware'][] = 'jwt.auth';
                }
                $router->addRoute(
                    $method,
                    $uri,
                    $action
                );
            }
        }
    }
}

// Allow options methods on all routes?
// TODO do I really want this to be all routes?
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);
