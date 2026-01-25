<?php

use App\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ValidateSignature;

uses(RefreshDatabase::class);

it('includes TrustHosts in the global middleware stack', function () {
    $defaults = (new ReflectionClass(\App\Http\Kernel::class))->getDefaultProperties();
    expect($defaults['middleware'])->toContain(\App\Http\Middleware\TrustHosts::class);
});

it('registers signed middleware alias', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $aliasesProp = (new ReflectionClass($kernel))->getProperty('middlewareAliases');
    $aliasesProp->setAccessible(true);
    $aliases = $aliasesProp->getValue($kernel);

    expect($aliases)->toHaveKey('signed');
    expect($aliases['signed'])->toBe(ValidateSignature::class);
});
