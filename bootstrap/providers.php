<?php

use App\Providers\AppServiceProvider;
use App\Providers\CollectorRegistryProvider;
use App\Providers\DomainValidatorServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\KubernetesClientServiceProvider;
use App\Providers\ReCaptchaServiceProvider;

return [
    AppServiceProvider::class,
    CollectorRegistryProvider::class,
    DomainValidatorServiceProvider::class,
    HorizonServiceProvider::class,
    KubernetesClientServiceProvider::class,
    ReCaptchaServiceProvider::class,
];
