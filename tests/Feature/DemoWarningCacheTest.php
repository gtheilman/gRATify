<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches demo-warning payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $this->getJson('/api/demo-warning')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached demo-warning payload on repeat requests', function () {
    Cache::flush();

    $first = $this->getJson('/api/demo-warning')
        ->assertOk()
        ->json();

    $second = $this->getJson('/api/demo-warning')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
