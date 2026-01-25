<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('caches shortlink providers payload for a short ttl', function () {
    Cache::flush();
    Cache::spy();

    config([
        'bitly.accesstoken' => 'bitly-test-token',
        'services.tinyurl.token' => 'tinyurl-test-token',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});

it('serves cached shortlink providers payload on repeat requests', function () {
    Cache::flush();

    config([
        'bitly.accesstoken' => 'bitly-test-token',
        'services.tinyurl.token' => 'tinyurl-test-token',
    ]);

    $user = User::factory()->create();

    $first = $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    $second = $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    expect($second)->toBe($first);
});
