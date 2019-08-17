<?php

$router->group(['prefix' => 'backend', 'middleware' => ['backend.auth']], function () use ($router) {
  $router->group(['prefix' => 'wiki'], function () use ($router) {
    // GET
    $router->get('database/countUnclaimed', ['uses' => 'WikiDbsController@countUnclaimed']);
    // POST
    $router->post('database/recordCreation', ['uses' => 'WikiDbController@create']);
  });
});
