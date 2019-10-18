<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class MineTest extends TestCase
{
    protected $route = 'wiki/mine';

    use OptionsRequestAllowed;
}
