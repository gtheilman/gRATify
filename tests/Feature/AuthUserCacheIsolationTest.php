<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not reuse cached /api/user across users', function () {
    Cache::flush();

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $payloadA = $this->actingAs($userA, 'web')
        ->getJson('/api/user')
        ->assertOk()
        ->json();

    $payloadB = $this->actingAs($userB, 'web')
        ->getJson('/api/user')
        ->assertOk()
        ->json();

    expect($payloadA['id'])->toBe($userA->id);
    expect($payloadB['id'])->toBe($userB->id);
});
