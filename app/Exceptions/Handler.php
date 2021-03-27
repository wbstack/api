<?php

namespace App\Exceptions;

use \Throwable;
use GlueDev\Laravel\Stackdriver\StackdriverExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
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
     * @param  \Throwable  $throwable
     * @return void
     */
    public function report(Throwable $throwable)
    {
        StackdriverExceptionHandler::report($throwable);
        parent::report($throwable);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $throwable
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $throwable)
    {
        return parent::render($request, $throwable);
    }
}
