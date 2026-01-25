<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has expected logging defaults', function () {
    $logging = config('logging');
    expect($logging)->toHaveKeys(['default', 'channels']);
    expect($logging['channels'])->toHaveKey('stack');
    expect($logging['channels']['stack']['channels'])->toContain('single');
});

it('has expected cache configuration', function () {
    $cache = config('cache');
    expect($cache['default'])->toBe(env('CACHE_STORE', 'file'));
    expect($cache['stores'])->toHaveKey('file');
    expect($cache['stores'])->toHaveKey('redis');
});

it('has expected database configuration', function () {
    $db = config('database');
    expect($db['default'])->toBe(env('DB_CONNECTION', 'mysql'));
    expect($db['connections'])->toHaveKeys(['mysql', 'sqlite']);
});

it('has expected mail configuration', function () {
    $mail = config('mail');
    expect($mail['default'])->toBe(env('MAIL_MAILER', 'smtp'));
    expect($mail['mailers'])->toHaveKey('smtp');
    expect($mail['from'])->toHaveKeys(['address', 'name']);
});

it('has expected session configuration', function () {
    $session = config('session');
    expect($session['driver'])->toBe(env('SESSION_DRIVER', 'file'));
    expect($session)->toHaveKey('lifetime');
});

it('has expected broadcasting configuration', function () {
    $broadcasting = config('broadcasting');
    expect($broadcasting['default'])->toBe(env('BROADCAST_DRIVER', 'log'));
    expect($broadcasting['connections'])->toHaveKeys(['log', 'pusher', 'redis', 'null']);
});
