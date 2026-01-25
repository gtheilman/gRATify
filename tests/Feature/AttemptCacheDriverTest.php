<?php

use Illuminate\Support\Facades\Route;

it('attempts store route is not throttled to avoid db cache dependency', function () {
    $route = collect(Route::getRoutes())->first(fn ($r) => $r->getName() === 'attempts.store');

    $middleware = $route?->gatherMiddleware() ?? [];

    expect($middleware)->not->toContain('throttle:attempts');
});
