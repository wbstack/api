<?php

$router->group(['prefix' => 'backend', 'middleware' => ['backend.auth']], function () use ($router) {
  $router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('database/countUnclaimed', ['uses' => 'WikiDbsController@countUnclaimed']);
    $router->get('getWikiForDomain', ['uses' => 'WikiController@getWikiForDomain']);
    // POST
    $router->post('database/recordCreation', ['uses' => 'WikiDbController@create']);
  });
});
