<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('requires auth for logout endpoint', function () {
    $user = User::factory()->create([
        'email' => 'refresh@example.com',
        'password' => Hash::make('secret123'),
    ]);

    // Unauthenticated calls are blocked
    $this->postJson('/logout')->assertStatus(401);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'refresh@example.com',
        'password' => 'secret123',
    ])->assertOk();

    // Authenticated calls succeed
    $this->withCsrfToken()->postJson('/logout')->assertOk();
});
