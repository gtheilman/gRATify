<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches auth me payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached auth me payload on repeat requests', function () {
    Cache::flush();

    $user = User::factory()->create(['role' => 'admin']);

    $first = $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->json();

    $second = $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
