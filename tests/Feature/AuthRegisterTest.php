<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates required fields for auth register', function () {
    $this->postJson('/api/auth/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'email', 'displayName', 'password']);
});

it('registers a user and returns success status', function () {
    $this->postJson('/api/auth/register', [
        'username' => 'newuser',
        'email' => 'newuser@example.com',
        'displayName' => 'New User',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'username' => 'newuser',
        'name' => 'New User',
    ]);
});

it('rejects duplicate email registrations', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/auth/register', [
        'username' => 'dupuser',
        'email' => 'dup@example.com',
        'displayName' => 'Dup User',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects short passwords', function () {
    $this->postJson('/api/auth/register', [
        'username' => 'shortpass',
        'email' => 'shortpass@example.com',
        'displayName' => 'Short Pass',
        'password' => 'short',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects invalid email format', function () {
    $this->postJson('/api/auth/register', [
        'username' => 'bademail',
        'email' => 'not-an-email',
        'displayName' => 'Bad Email',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects overly long username and displayName', function () {
    $long = str_repeat('a', 256);

    $this->postJson('/api/auth/register', [
        'username' => $long,
        'email' => 'long@example.com',
        'displayName' => $long,
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['username', 'displayName']);
});
