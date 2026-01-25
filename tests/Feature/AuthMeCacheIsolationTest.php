<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not reuse cached /api/auth/me across users', function () {
    Cache::flush();

    $userA = User::factory()->create(['role' => 'admin']);
    $userB = User::factory()->create(['role' => 'editor']);

    $payloadA = $this->actingAs($userA, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->json();

    $payloadB = $this->actingAs($userB, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->json();

    expect($payloadA['id'])->toBe($userA->id);
    expect($payloadB['id'])->toBe($userB->id);
});
