<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Override trait method..
    // https://stackoverflow.com/questions/11939166/how-to-override-trait-function-and-call-it-from-the-overridden-function
    use CreatesApplication {
        createApplication as protected traitCreateApplication;
    }

    public function createApplication()
    {
      // Run all jobs sync...
      putenv('QUEUE_CONNECTION=sync');
      return $this->traitCreateApplication();
    }

}
