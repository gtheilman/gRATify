<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Support\Errors;
use App\Http\Middleware\TrackOperationalSignals;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->priority([
            StartSession::class,
            ShareErrorsFromSession::class,
            Authenticate::class,
            ThrottleRequests::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);

        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            VerifyCsrfToken::class,
            TrackOperationalSignals::class,
            ThrottleRequests::class . ':api',
            SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::UNAUTHENTICATED, 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::FORBIDDEN, 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return Errors::response(Errors::NOT_FOUND, 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, $request) {
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
    })->create();
