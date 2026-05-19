<?php

return [
    'proxies' => (function () {
        $split = array_filter(
            explode(',', (string) env('TRUSTED_PROXY_PROXIES', ''))
        );
        return match (count($split)) {
            0 => null,
            1 => $split[0],
            default => $split,
        };
    })(),
];
