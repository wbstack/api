<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class DetailsTest extends TestCase
{
    protected $route = 'wiki/details';

    use OptionsRequestAllowed;
}
