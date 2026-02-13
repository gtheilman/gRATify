<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();
});

it('tracks unauthenticated auth me responses in operational signals', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'web')
        ->getJson('/api/admin/operational-signals')
        ->assertOk()
        ->assertJsonPath('totals.auth_me_401', 1)
        ->assertJsonPath('window_minutes', 15);
});

it('does not track unauthenticated auth me responses from client referers', function () {
    $this->withHeader('Referer', 'https://tbl.gratify-app.com/client/82adc345')
        ->getJson('/api/auth/me')
        ->assertStatus(401);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'web')
        ->getJson('/api/admin/operational-signals')
        ->assertOk()
        ->assertJsonPath('totals.auth_me_401', 0)
        ->assertJsonPath('window_minutes', 15);
});

it('rejects operational signal report for non-admin users', function () {
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor, 'web')
        ->getJson('/api/admin/operational-signals')
        ->assertStatus(403);
});
