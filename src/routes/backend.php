<?php

$router->group(['prefix' => 'backend', 'middleware' => ['backend.auth']], function () use ($router) {
    $router->group(['prefix' => 'wiki'], function () use ($router) {
        // GET
        $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
    });
});
