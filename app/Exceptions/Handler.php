<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

use Google\Cloud\ErrorReporting\V1beta1\ReportErrorsServiceClient;
use Google\Cloud\ErrorReporting\V1beta1\ReportedErrorEvent;
use Google\Cloud\ErrorReporting\V1beta1\ServiceContext;

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
        Log::debug(__FILE__, ['>>>>>>>> starting error reporting']);
        Log::debug(__FILE__, [$e->getMessage()]);

        if (config('stackdriver.enabled')) {
            Log::debug(__FILE__, ['reporting error via stackdrier']);
            $reportErrorsServiceClient = new ReportErrorsServiceClient([
                'credentials' => config('stackdriver.credentials.keyFilePath'),
            ]);

            $formattedProjectName = $reportErrorsServiceClient->projectName(
                config('stackdriver.credentials.projectId')
            );

            // $eventServiceContext = new ServiceContext();
            $event = (new ReportedErrorEvent())
                // ->setServiceContext($eventServiceContext)
                ->setMessage($e);
            try {
                $response = $reportErrorsServiceClient->reportErrorEvent($formattedProjectName, $event);
                Log::debug(__FILE__, [$response]);
            } finally {
                $reportErrorsServiceClient->close();
            }
        }
        
        parent::report($e);

        Log::debug(__FILE__, ['<<<<<<<< finished error reporting']);
    }
}
