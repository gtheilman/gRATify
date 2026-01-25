<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Support\Errors;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Ensures API routes always emit the standardized error envelope.
 */
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

        // Normalize auth failures for API consumers.
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::UNAUTHENTICATED, 401);
            }
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::FORBIDDEN, 403);
            }
        });

        $this->renderable(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::NOT_FOUND, 404);
            }
        });

        // Map common HTTP exception status codes into the shared error envelope.
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if (! ($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            $status = $e->getStatusCode();
            $codeMap = [
                401 => Errors::UNAUTHENTICATED,
                403 => Errors::FORBIDDEN,
                404 => Errors::NOT_FOUND,
                423 => Errors::LOCKED,
            ];

            if (! isset($codeMap[$status])) {
                return null;
            }

            return Errors::response($codeMap[$status], $status);
        });

        // Example reportable hook (kept empty to match Laravel 12 default).
        $this->reportable(function (Throwable $e) {
            // Add custom logging/monitoring here if needed.
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return Errors::response(Errors::UNAUTHENTICATED, 401);
        }

        return parent::unauthenticated($request, $exception);
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // Safety net: ensure JSON errors still use the envelope if renderable hooks miss.
            if ($e instanceof AuthenticationException) {
                return Errors::response(Errors::UNAUTHENTICATED, 401);
            }

            if ($e instanceof AuthorizationException) {
                return Errors::response(Errors::FORBIDDEN, 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return Errors::response(Errors::NOT_FOUND, 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $codeMap = [
                    401 => Errors::UNAUTHENTICATED,
                    403 => Errors::FORBIDDEN,
                    404 => Errors::NOT_FOUND,
                    423 => Errors::LOCKED,
                ];

                if (isset($codeMap[$status])) {
                    return Errors::response($codeMap[$status], $status);
                }
            }
        }

        return parent::render($request, $e);
    }
}
