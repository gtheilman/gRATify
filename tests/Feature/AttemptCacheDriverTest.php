<?php

use Illuminate\Support\Facades\Route;

it('attempts store route is not throttled to avoid db cache dependency', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->getName() === 'attempts.store');

    $middleware = $route?->gatherMiddleware() ?? [];

    expect($middleware)->not->toContain('throttle:attempts');
    expect($middleware)->not->toContain('throttle:api');
});

it('attempts bulk route is not throttled by api limiter', function () {
    $route = collect(Route::getRoutes())->first(function ($r) {
        return in_array('POST', $r->methods(), true) && $r->uri() === 'api/attempts/bulk';
    });

    $middleware = $route?->gatherMiddleware() ?? [];

    expect($middleware)->not->toContain('throttle:api');
});
