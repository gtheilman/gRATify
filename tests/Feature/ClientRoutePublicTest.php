<?php

declare(strict_types=1);

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

it('routes /client through the SPA controller', function () {
    $route = app('router')->getRoutes()->match(Request::create('/client/demo123'));

    expect($route->getAction()['controller'] ?? null)->toBe(ApplicationController::class);
    expect($route->uri())->toBe('{any?}');
});

it('serves the client route without auth when assets are built', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! File::exists($manifestPath)) {
        $this->markTestSkipped('Vite manifest not present; run npm run build to generate it.');
    }

    $response = $this->get('/client/demo123');
    $response->assertOk();
    expect($response->status())->not->toBeIn([301, 302]);
});
