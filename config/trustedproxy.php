<?php

return [
    'proxies' => (function () {
        $split = array_filter(
            explode(',', env('TRUSTED_PROXY_PROXIES', ''))
        );
        switch (count($split)) {
            case 0:
                return null;
            case 1:
                return $split[0];
            default:
                return $split;
        }
    })(),
];
