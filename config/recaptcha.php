<?php

return [
    'secret_key' => env('RECAPTCHA_V3_SECRET_KEY', 'config/recaptcha.php: no-secret-key-set!'),
    'min_score' => env('RECAPTCHA_V3_MIN_SCORE', 0.5),
];
