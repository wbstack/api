<?php

namespace Tests\Routes\Traits;

trait PostRequestNeedAuthentication {
    public function testPostRequestWhenUnauthenticatedRespondes401() {
        $response = $this->json('POST', $this->route);
        $this->assertEquals(401, $response->status());
    }
}
