<?php

namespace App\Http\Curl;

interface HttpRequest
{
    public function setOptions(array $options);
    public function execute();
    public function getInfo($name);
    public function close();
    public function error();
    public function reset();

}