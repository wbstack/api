<?php

namespace App\Tests\Routes\Traits;

trait OptionsRequestAllowed {

  public function testOptionsRequestResponds200()
  {
      $this->call('OPTIONS', $this->route);
      $this->assertEquals(200, $this->response->status());
  }

}
