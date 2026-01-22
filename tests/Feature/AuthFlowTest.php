<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('rejects login with invalid credentials and returns error envelope', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'user@example.com',
        'password' => 'wrong',
    ])
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonPath('error.message', 'Unauthenticated');
});

it('validates login payload', function () {
    $this->postJson('/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('returns 401 for logout when unauthenticated', function () {
    $this->postJson('/logout')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonPath('error.message', 'Unauthenticated');
});

it('returns 401 for auth/me when unauthenticated', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonPath('error.message', 'Unauthenticated');
});

it('logs in, fetches /auth/me, and logs out successfully', function () {
    $user = User::factory()->create([
        'email' => 'user2@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'user2@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', 'user2@example.com');

    $this->withCsrfToken()->postJson('/logout')->assertOk();
    $this->getJson('/api/auth/me')->assertStatus(401);
});
