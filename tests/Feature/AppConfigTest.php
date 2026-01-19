<?php

use Illuminate\Support\Facades\Config;

uses()->group('config');

it('loads core application providers and aliases', function () {
    $providers = Config::get('app.providers');
    $aliases = Config::get('app.aliases');

    expect($providers)->toBeArray()
        ->toContain(App\Providers\AppServiceProvider::class)
        ->toContain(App\Providers\AuthServiceProvider::class)
        ->toContain(App\Providers\EventServiceProvider::class)
        ->toContain(App\Providers\RouteServiceProvider::class);

    expect($aliases)->toBeArray()
        ->and(array_keys($aliases))->toContain('Route')
        ->and($aliases['Route'])->toBe(Illuminate\Support\Facades\Route::class);
});

it('uses the SPA root as the post-login home path', function () {
    expect(\App\Providers\RouteServiceProvider::HOME)->toBe('/');
});
