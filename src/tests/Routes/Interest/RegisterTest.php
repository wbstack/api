<?php

namespace Tests\Routes\Interest;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected $route = 'interest/register';

    use OptionsRequestAllowed;
}
