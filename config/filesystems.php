<?php

$fileSystems = [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'static-assets' => [
            'driver' => 's3',
            'key' => env('STATIC_STORAGE_ACCESS_KEY'),
            'secret' => env('STATIC_STORAGE_SECRET_KEY'),
            'region' => env('STATIC_STORAGE_REGION', 'us-east-1'),
            'bucket' => env('STATIC_STORAGE_BUCKET_NAME'),
            'url' => env('STATIC_STORAGE_URL'),
            'endpoint' => env('STATIC_STORAGE_ENDPOINT'),
            'bucket_endpoint' => boolval(env('STATIC_STORAGE_USE_BUCKET_ENDPOINT', '0')),
            'use_path_style_endpoint' => boolval(env('STATIC_STORAGE_USE_PATH_STYLE_ENDPOINT', '1')),
        ],
    ],
];

return $fileSystems;
