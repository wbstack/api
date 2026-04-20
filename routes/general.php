<?php

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 *
 * @var Router $router
 */

// GET
use Illuminate\Routing\Router;

$router->get(
    'healthz',
    function () {
        return 'It\'s Alive';
    }
);
