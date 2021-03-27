<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use OptionsRequestAllowed;
}
