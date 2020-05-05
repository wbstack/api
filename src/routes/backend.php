<?php

$router->get(
    'healthz',
    function() {
        return 'It\'s Alive';
    }
);

$router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
});

$router->group(['prefix' => 'event'], function () use ($router) {
    // POST
    $router->post('pageUpdate', ['uses' => 'EventController@pageUpdate']);
});

$router->group(['prefix' => 'qs'], function () use ($router) {
    // GET
    $router->get('getBatches', ['uses' => 'QsController@getBatches']);
    // POST
    $router->post('markDone', ['uses' => 'QsController@markBatchesDone']);
    $router->post('markFailed', ['uses' => 'QsController@markBatchesFailed']);
});
