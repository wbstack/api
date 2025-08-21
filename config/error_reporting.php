<?php

return [
    // Enable error reporting
    'enable' => (bool) env('STACKDRIVER_ENABLED', false),
    'logName' => env('STACKDRIVER_LOGGING_NAME', 'error-log'),
    'serviceId' => env('STACKDRIVER_ERROR_REPORTING_SERVICE_ID', env('APP_NAME')),
    'versionId' => env('STACKDRIVER_ERROR_REPORTING_VERSION_ID', '1.0.0'),
    'LoggingClient' => [
        // The project ID from the Google Developer's Console.
        'projectId' => env('STACKDRIVER_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT_ID')),
        // The full path to your service account credentials .json file retrieved from the Google Developers Console.
        'keyFilePath' => env('STACKDRIVER_KEY_FILE_PATH', ''),
        // Seconds to wait before timing out the request.
        // **Defaults to** `0` with REST and `60` with gRPC.
        'requestTimeout' => 0,
        // Number of retries for a failed request.
        'retries' => 3,
        // The transport type used for requests.
        // May be either `grpc` or `rest`.
        // **Defaults to** `grpc` if gRPC support  is detected on the system.
        'transport' => null,
    ],
    'PsrLogger' => [
        // Determines whether or not to use background batching.
        'batchEnabled' => (bool) env('STACKDRIVER_LOGGING_BATCH_ENABLED', true),
        //  Whether or not to output debug information.
        'debugOutput' => false,
        // A set of options for a BatchJob.
        'batchOptions' => [
            'batchSize' => 50,
            'callPeriod' => 2.0,
            'workerNum' => 1,
        ],
    ],
];
