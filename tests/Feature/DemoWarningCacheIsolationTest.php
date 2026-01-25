<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('serves cached demo-warning payload for repeated requests', function () {
    Cache::flush();

    $first = $this->getJson('/api/demo-warning')
        ->assertOk()
        ->json();

    $second = $this->getJson('/api/demo-warning')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
