<?php

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 * @var \Laravel\Lumen\Routing\Router $router
 */

// GET
$router->get(
    'healthz',
    function () {
        return 'It\'s Alive';
    }
);

$router->group(['prefix' => 'ingress'], function () use ($router) {
    // GET
    $router->get('getWikiVersionForDomain', ['uses' => 'IngressController@getWikiVersionForDomain']);
});

$router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
});

$router->group(['prefix' => 'event'], function () use ($router) {
    // POST
    $router->post('pageUpdate', ['uses' => 'EventController@pageUpdate']);
    $router->post('pageUpdateBatch', ['uses' => 'EventController@pageUpdateBatch']);
});

$router->group(['prefix' => 'qs'], function () use ($router) {
    // GET
    $router->get('getBatches', ['uses' => 'QsController@getBatches']);
    // POST
    $router->post('markDone', ['uses' => 'QsController@markBatchesDone']);
    $router->post('markFailed', ['uses' => 'QsController@markBatchesFailed']);
});
