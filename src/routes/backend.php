<?php

$router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
});


$router->group(['prefix' => 'event'], function () use ($router) {
    // POST
    $router->post('pageUpdate', ['uses' => 'EventController@pageUpdate']);
});
