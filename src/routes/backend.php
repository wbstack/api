<?php

$router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
});
