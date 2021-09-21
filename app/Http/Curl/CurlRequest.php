<?php
namespace App\Http\Curl;

class CurlRequest implements HttpRequest
{
    private $handle = null;

    public function __construct() {
        $this->reset();
    }

    public function setOptions( array $options ) {
        curl_setopt_array( $this->handle, $options );
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function error() {
        return curl_error($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    public function close() {
        if( $this->handle ) {
            curl_close($this->handle);
            $this->handle = null;
        }
    }

    public function reset() {
        if( $this->handle ) {
            $this->close();
        }

        $this->handle = curl_init();
    }
}
