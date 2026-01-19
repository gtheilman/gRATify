<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects duplicate emails on user update', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $existing = User::factory()->create(['email' => 'existing@example.com']);
    $target = User::factory()->create(['email' => 'target@example.com']);

    $this->actingAs($admin, 'web')
        ->patchJson("/api/user-management/users/{$target->id}", [
            'email' => $existing->email,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
