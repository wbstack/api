<?php

use App\Metrics\FailedQsBatches;
use App\Metrics\PendingQsBatches;
use App\Metrics\WikiEntityImports;
use LKDevelopment\HorizonPrometheusExporter\Exporter\CurrentMasterSupervisors;
use LKDevelopment\HorizonPrometheusExporter\Exporter\CurrentProcessesPerQueue;
use LKDevelopment\HorizonPrometheusExporter\Exporter\CurrentWorkload;
use LKDevelopment\HorizonPrometheusExporter\Exporter\FailedJobsPerHour;
use LKDevelopment\HorizonPrometheusExporter\Exporter\HorizonStatus;
use LKDevelopment\HorizonPrometheusExporter\Exporter\JobsPerMinute;
use LKDevelopment\HorizonPrometheusExporter\Exporter\RecentJobs;
use LKDevelopment\HorizonPrometheusExporter\Http\Middleware\IPWhitelistingMiddleware;

return [
    'enabled' => getenv('ROUTES_LOAD_BACKEND') == 1,
    'namespace' => 'platform_api',
    /**
     * You can change the default endpoint to something other than metrics.
     * Keep in mind that the change needs to be reflected in your Prometheus configuration as well.
     */
    'url' => 'metrics',

    /**
     * You can enable or disable or even create own exporters by simply implementing the LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter Contract.
     * If you want to disable oder enable a Exporter just comment the specific line out.
     * If you want to add your own Exporter just add the Class Name to this array
     */
    'exporters' => [
        CurrentMasterSupervisors::class,
        JobsPerMinute::class,
        CurrentWorkload::class,
        CurrentProcessesPerQueue::class,
        FailedJobsPerHour::class,
        HorizonStatus::class,
        RecentJobs::class,
        FailedQsBatches::class,
        PendingQsBatches::class,
        WikiEntityImports::class,
    ],

    /**
     * IP Whitelisting, you may don't want to expose your metrics on the internet so you can add the IP addresses of your Prometheus Server here.
     */
    'ip_whitelist' => [
        // Keep empty to allow all IP addresses
    ],

    /**
     * You can change the Middleware which is used for the IP whitelisting.  You can add your own, like a token based authentication.
     */
    'middleware' => IPWhitelistingMiddleware::class,

    /**
     * Allow storage to be wiped after a render of data in metrics controller
     */
    'wipe_storage_after_render' => false,
];
