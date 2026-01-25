<?php

use Illuminate\Support\Facades\File;

it('composer.json pins Laravel 12-compatible first-party packages', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true);

    $require = $composer['require'] ?? [];
    $requireDev = $composer['require-dev'] ?? [];

    expect($require['laravel/framework'] ?? '')->toStartWith('^12.');
    expect($require['laravel/tinker'] ?? '')->toStartWith('^2.');
    expect($requireDev['laravel/pint'] ?? '')->not->toBeNull();
    expect($requireDev['phpunit/phpunit'] ?? '')->toStartWith('^11.');
});

it('does not depend on legacy laravel-mix', function () {
    $composer = File::get(base_path('composer.json'));
    expect($composer)->not->toContain('laravel-mix');
});
