<?php

use Illuminate\Routing\Router;

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 *
 * @var Router $router
 */

// GET
$router->get(
    'healthz',
    fn() => 'It\'s Alive'
);
