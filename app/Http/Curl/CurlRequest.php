<?php

namespace App\Http\Curl;

class CurlRequest implements HttpRequest {
    private $handle = null;

    public function __construct() {
        $this->reset();
    }

    /**
     * @return void
     */
    public function setOptions(array $options) {
        curl_setopt_array($this->handle, $options);
    }

    /**
     * @return bool|string
     */
    public function execute() {
        return curl_exec($this->handle);
    }

    /**
     * @return string
     */
    public function error() {
        return curl_error($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    /**
     * @return void
     */
    public function close() {
        if ($this->handle) {
            curl_close($this->handle);
            $this->handle = null;
        }
    }

    /**
     * @return void
     */
    public function reset() {
        if ($this->handle) {
            $this->close();
        }

        $this->handle = curl_init();
    }
}
