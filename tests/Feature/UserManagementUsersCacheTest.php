<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches user-management users list for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached user-management users on repeat requests', function () {
    Cache::flush();

    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create();

    $first = $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->json();

    $second = $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
