<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

use Google\Cloud\ErrorReporting\V1beta1\ReportErrorsServiceClient;
use Google\Cloud\ErrorReporting\V1beta1\ReportedErrorEvent;

class Handler extends ExceptionHandler
{

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        if (config('stackdriver.enabled')) {
            $reportErrorsServiceClient = new ReportErrorsServiceClient();
            $formattedProjectName = $reportErrorsServiceClient->projectName(
                config('credentials.projectId')
            );
            $event = new ReportedErrorEvent();
    
            try {
                $response = $reportErrorsServiceClient->reportErrorEvent($formattedProjectName, $event);
                Log::debug(__FILE__, [$response]);
            } finally {
                $reportErrorsServiceClient->close();
            }    
        }

        parent::report($e);
    }
}
