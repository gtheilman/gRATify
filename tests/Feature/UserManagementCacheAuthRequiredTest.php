<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth for user management list and show', function () {
    $this->getJson('/api/user-management/users')->assertStatus(401);

    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk();

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$target->id}")
        ->assertOk();
});
