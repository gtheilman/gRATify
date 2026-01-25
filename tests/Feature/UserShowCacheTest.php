<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches user-management show payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached user-management show payload on repeat requests', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'editor']);

    $first = $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk()
        ->json();

    $second = $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
