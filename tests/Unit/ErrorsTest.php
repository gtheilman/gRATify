<?php

use App\Support\Errors;
use Tests\TestCase;

uses(TestCase::class);

it('returns consistent messages for known error codes', function () {
    expect(Errors::message(Errors::UNAUTHENTICATED))->toBe('Unauthenticated')
        ->and(Errors::message(Errors::FORBIDDEN))->toBe('Forbidden')
        ->and(Errors::message(Errors::NOT_FOUND))->toBe('Not Found')
        ->and(Errors::message(Errors::LOCKED))->toBe('Locked')
        ->and(Errors::message(Errors::INVALID_SCHEME))->toBe('Invalid scoring scheme');
});

it('builds a standard error envelope', function () {
    $payload = Errors::envelope(Errors::NOT_FOUND);

    expect($payload['error']['code'])->toBe('not_found')
        ->and($payload['error']['message'])->toBe('Not Found');
});
