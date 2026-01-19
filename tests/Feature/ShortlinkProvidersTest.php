<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes shortlink providers for authenticated users', function () {
    config([
        'bitly.accesstoken' => 'bitly-test-token',
        'services.tinyurl.token' => 'tinyurl-test-token',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    expect($response)->toMatchArray([
        'bitly' => true,
        'tinyurl' => true,
    ]);
});

it('returns false for shortlink providers when tokens are not configured', function () {
    config([
        'bitly.accesstoken' => null,
        'services.tinyurl.token' => null,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->getJson('/api/shortlink-providers')
        ->assertOk()
        ->json();

    expect($response)->toMatchArray([
        'bitly' => false,
        'tinyurl' => false,
    ]);
});

it('rejects invalid shortlink providers on assessment create', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/api/assessments', [
            'title' => 'Shortlink Validation Test',
            'shortlink_provider' => 'bogus',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['shortlink_provider']);
});
