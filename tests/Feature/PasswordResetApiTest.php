<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('sends reset link for a known email and hides existence for unknown', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/auth/password/email', ['email' => 'reset@example.com'])
        ->assertOk();

    // Unknown email still returns 200 with a generic message (does not reveal existence).
    $this->postJson('/api/auth/password/email', ['email' => 'missing@example.com'])
        ->assertOk()
        ->assertJsonStructure(['status', 'sent']);
});

it('validates reset payload and resets password with a valid token', function () {
    $user = User::factory()->create(['email' => 'reset2@example.com']);

    $token = Password::createToken($user);

    $this->postJson('/api/auth/password/reset', [
        'email' => 'reset2@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'token' => $token,
    ])->assertOk();
});

it('rejects reset without required fields', function () {
    $this->postJson('/api/auth/password/reset', [])
        ->assertStatus(422);
});
