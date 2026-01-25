<?php

use App\Services\Shortlinks\ShortlinkService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns bitly when available without errors', function () {
    config(['bitly.accesstoken' => 'token']);
    config(['services.tinyurl.token' => null]);

    Bitly::shouldReceive('getURL')
        ->once()
        ->andReturn('https://bit.ly/ok');

    $service = new ShortlinkService();
    [$url, $error] = $service->generateShortUrl('https://example.com/client/abc');

    expect($url)->toBe('https://bit.ly/ok')
        ->and($error)->toBeNull();
});

it('falls back to bitly after preferred tinyurl fails', function () {
    config(['bitly.accesstoken' => 'token']);
    config(['services.tinyurl.token' => 'token']);

    Http::fake([
        'https://api.tinyurl.com/create' => Http::response('rate limit', 429),
    ]);

    Bitly::shouldReceive('getURL')
        ->once()
        ->andReturn('https://bit.ly/ok');

    $service = new ShortlinkService();
    [$url, $error] = $service->generateShortUrl('https://example.com/client/abc', 'tinyurl');

    expect($url)->toBe('https://bit.ly/ok')
        ->and($error)->toContain('TinyURL:');
});

it('falls back to tinyurl when bitly throws', function () {
    config(['bitly.accesstoken' => 'token']);
    config(['services.tinyurl.token' => 'token']);

    Bitly::shouldReceive('getURL')
        ->once()
        ->andThrow(new Exception('bitly down'));

    Http::fake([
        'https://api.tinyurl.com/create' => Http::response([
            'data' => ['tiny_url' => 'https://tinyurl.com/ok'],
        ], 200),
    ]);

    $service = new ShortlinkService();
    [$url, $error] = $service->generateShortUrl('https://example.com/client/abc');

    expect($url)->toBe('https://tinyurl.com/ok')
        ->and($error)->toContain('Bitly: bitly down');
});
