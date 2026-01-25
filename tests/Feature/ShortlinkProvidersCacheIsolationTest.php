<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not reuse cached shortlink providers across users', function () {
    Cache::flush();

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    config([
        'bitly.accesstoken' => 'bitly-test-token',
        'services.tinyurl.token' => 'tinyurl-test-token',
    ]);

    $payloadA = $this->actingAs($userA, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    expect($payloadA)->toMatchArray(['bitly' => true, 'tinyurl' => true]);

    config([
        'bitly.accesstoken' => null,
        'services.tinyurl.token' => null,
    ]);

    $payloadB = $this->actingAs($userB, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    expect($payloadB)->toMatchArray(['bitly' => false, 'tinyurl' => false]);
});
