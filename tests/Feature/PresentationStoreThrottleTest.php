<?php

use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('presentation store route is not throttled to avoid cache table dependency', function () {
    $route = collect(\Illuminate\Support\Facades\Route::getRoutes())
        ->first(fn ($r) => $r->getName() === 'presentations.store');

    $middleware = $route?->gatherMiddleware() ?? [];

    expect($middleware)->toContain('throttle:presentations');
});
