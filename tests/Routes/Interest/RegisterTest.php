<?php

namespace Tests\Routes\Interest;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class RegisterTest extends TestCase
{
    protected $route = 'interest/register';

    use OptionsRequestAllowed;
}
