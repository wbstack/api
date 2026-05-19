<?php

namespace App\Exceptions;

use Absszero\ErrorReporting;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    #[\Override]
    public function register(): void {
        $this->reportable(function (Throwable $e): void {
            (new ErrorReporting)->report($e);
        });
    }
}
