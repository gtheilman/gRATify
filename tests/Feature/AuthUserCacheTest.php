<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches /api/user payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/user')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached /api/user payload on repeat requests', function () {
    Cache::flush();

    $user = User::factory()->create();

    $first = $this->actingAs($user, 'web')
        ->getJson('/api/user')
        ->assertOk()
        ->json();

    $second = $this->actingAs($user, 'web')
        ->getJson('/api/user')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
