<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use OptionsRequestAllowed;
}
