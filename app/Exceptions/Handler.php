<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Custom exception view handling.
        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof CustomException) {
                return response()->view('errors.custom', [], 500);
            }
        });

        // Example reportable hook (kept empty to match Laravel 12 default).
        $this->reportable(function (Throwable $e) {
            // Add custom logging/monitoring here if needed.
        });
    }
}
