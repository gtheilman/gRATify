<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('sends reset link for a known email and hides existence for unknown', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/auth/password/email', ['email' => 'reset@example.com'])
        ->assertOk()
        ->assertJsonStructure(['status', 'sent']);

    // Unknown email still returns 200 with a generic message (does not reveal existence).
    $this->postJson('/api/auth/password/email', ['email' => 'missing@example.com'])
        ->assertOk()
        ->assertJsonStructure(['status', 'sent']);
});

it('returns not enabled when mail is disabled', function () {
    config(['mail.enabled' => false]);

    $this->postJson('/api/auth/password/email', ['email' => 'reset@example.com'])
        ->assertOk()
        ->assertJson([
            'sent' => false,
            'status' => 'Password reset is not enabled.',
        ]);
});

it('returns not configured when mail transport is missing', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => null,
        'mail.mailers.smtp.port' => null,
    ]);

    $this->postJson('/api/auth/password/email', ['email' => 'reset@example.com'])
        ->assertOk()
        ->assertJson([
            'sent' => false,
            'status' => 'Password reset email is not configured.',
        ]);
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

it('returns 400 for invalid reset tokens', function () {
    User::factory()->create(['email' => 'invalidtoken@example.com']);

    $this->postJson('/api/auth/password/reset', [
        'email' => 'invalidtoken@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'token' => 'invalid-token',
    ])
        ->assertStatus(400)
        ->assertJsonStructure(['status']);
});
