<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'mysql' => [
            'read' => [
                'host' => [
                    // ConnectionFactory does a Arr::shuffle of these currently
                    // These can not currently be ordered or weighted
                    // Which mean 50/50 to the master and replcias right now...
                    // But this does mean if the replicas are all down, the master will still used for reads..
                    // Upstream: https://github.com/laravel/ideas/issues/2225
                    env('DB_HOST_READ'),
                    env('DB_HOST_WRITE'),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_HOST_WRITE'),
                ],
            ],
            // If the sticky option is enabled and a "write" operation has been performed
            // against the database during the current request cycle,
            // any further "read" operations will use the "write" connection.
            'sticky'    => true,
            'driver'    => 'mysql',
            'url' => env('DATABASE_URL'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mw' => [
            // TODO one day these might be a different server...
            'read' => [
                'host' => [
                    env('DB_HOST_READ'),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_HOST_WRITE'),
                ],
            ],
            // If the sticky option is enabled and a "write" operation has been performed
            // against the database during the current request cycle,
            // any further "read" operations will use the "write" connection.
            'sticky'    => true,
            'driver' => 'mysql',
            'url' => env('MW_DATABASE_URL'),
            'port' => env('MW_DB_PORT', '3306'),
            'database' => env('MW_DB_DATABASE'),
            'username' => env('MW_DB_USERNAME'),
            'password' => env('MW_DB_PASSWORD', ''),
            'unix_socket' => env('MW_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        // 5.8 -> 6.0 of laravel, the default client switched to phpredis
        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'predis'),
            'prefix' => Str::slug(env('APP_NAME', 'laravel'), '_').'_database_',
        ],

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],

        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

        'metrics' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
        ],
    ],

];
