<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
uses(RefreshDatabase::class);

it('logs in with session auth and allows me/logout', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
    $this->postJson('/logout')->assertStatus(401);

    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'user@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonFragment(['email' => 'user@example.com']);

    $this->withCsrfToken()->postJson('/logout')->assertOk();
    $this->getJson('/api/auth/me')->assertStatus(401);
});
