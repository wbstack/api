<?php

namespace Tests\Routes\Traits;

trait OptionsRequestAllowed
{
    public function testOptionsRequestResponds200()
    {
        $response = $this->json('OPTIONS', $this->route);
        $this->assertEquals(200, $response->status());
    }
}
