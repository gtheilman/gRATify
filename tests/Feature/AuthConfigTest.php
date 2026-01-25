<?php

use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Reset auth config to file defaults to avoid cross-test mutation.
    Config::set('auth', require config_path('auth.php'));
});

it('uses api as the default guard', function () {
    expect(config('auth.defaults.guard'))->toBe('web');
});

it('uses session-based web guard', function () {
    $web = config('auth.guards.web');
    expect($web['driver'])->toBe('session');
    expect($web['provider'])->toBe('users');
});

it('does not configure a jwt api guard', function () {
    expect(config('auth.guards.api'))->toBeNull();
});
