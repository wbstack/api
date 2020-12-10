<?php


/** @var \Laravel\Lumen\Routing\Router $router */

// GET
$router->get(
    'healthz',
    function() {
        return 'It\'s Alive';
    }
);
