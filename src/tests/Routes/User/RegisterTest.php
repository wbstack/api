<?php

namespace App\Tests\Routes\User;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class RegisterTest extends TestCase {

    protected $route = 'user/register';

    use OptionsRequestAllowed;

}
