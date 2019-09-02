<?php

namespace App\Tests;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        // Run all jobs sync...
        putenv('QUEUE_CONNECTION=sync');

        return require __DIR__.'/../bootstrap/app.php';
    }
}
