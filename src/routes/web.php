<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/** @var Router $router */

use Laravel\Lumen\Routing\Router;

$wwRoutes = [
    'front' => [
        'GET' => [
            'noauth' => [
                'wiki/count' => 'WikiController@count',
                'wiki/list' => 'WikiController@list',
            ],
            'auth' => [
                'wiki/mine' => 'WikiController@getForUser'
            ],
        ],
        'POST' => [
            'auth' => [
                'user/self' => 'UserController@self',
                'wiki/create' => 'WikiController@create',
            ],
            'noauth' => [
                'auth/login' => 'AuthController@authenticate',
                'user/register' => 'UserController@register',
                'interest/register' => 'InterestController@recordCreation',
            ],
        ],
    ],
    'back' => [
        'GET' => [
            'auth' => [
                'wiki/database/countUnclaimed' => 'WikiDbController@countUnclaimed',
                'wiki/getWikiForDomain' => 'WikiController@getWikiForDomain',
            ],
        ],
        'POST' => [
            'auth' => [
                'wiki/database/recordCreation' => 'WikiDbController@recordCreation',
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
