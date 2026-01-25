<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns user list payload with expected fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'username',
                'email',
                'role',
                'assessments_count',
            ],
        ])
        ->assertJsonFragment(['id' => $admin->id])
        ->assertJsonFragment(['id' => $user->id]);
});

it('returns user show payload with expected fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin, 'web')
        ->getJson("/api/user-management/users/{$user->id}")
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'name',
            'username',
            'email',
            'role',
            'assessments_count',
        ])
        ->assertJsonPath('id', $user->id);
});

it('returns user update payload with expected fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'editor']);

    $this->actingAs($admin, 'web')
        ->patchJson("/api/user-management/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('email', 'updated@example.com');
});
