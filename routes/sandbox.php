<?php

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 *
 * @var Router $router
 */

use Illuminate\Routing\Router;

$router->post('sandbox/create', ['uses' => 'Sandbox\SandboxController@create'])
    ->middleware('throttle:5,1');
