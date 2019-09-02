<?php

namespace App\Tests\Routes\Traits;

trait CrossSiteHeadersOnOptions
{
    public function testCrossSiteHeadersOnOptions()
    {
        $this->call('OPTIONS', $this->route);
        $this->assertTrue($this->response->headers->has('Access-Control-Allow-Origin'));
        $this->assertTrue($this->response->headers->has('Access-Control-Allow-Methods'));
        $this->assertTrue($this->response->headers->has('Access-Control-Allow-Headers'));
        $this->assertTrue($this->response->headers->has('Access-Control-Allow-Credentials'));
        $this->assertTrue($this->response->headers->has('Access-Control-Max-Age'));
    }
}
