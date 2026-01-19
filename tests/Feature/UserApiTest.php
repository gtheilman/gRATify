<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Basic user/auth API coverage to guard further migrations.
 */
uses(RefreshDatabase::class);

it('returns 401 for /api/user when unauthenticated', function () {
    $this->getJson('/api/user')->assertStatus(401);
});

it('returns the current user for /api/user when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonFragment(['id' => $user->id]);
});

it('allows admin to list users and blocks non-admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $regular = User::factory()->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk();

    $this->actingAs($regular, 'web')
        ->getJson('/api/user-management/users')
        ->assertStatus(403);
});

it('allows a user to change their password with the correct old password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpass'),
    ]);

    $this->actingAs($user, 'web')
        ->postJson('/api/change-password/', [
            'user_id' => $user->id,
            'old_password' => 'oldpass',
            'new_password' => 'newpass',
            'new_password_confirmation' => 'newpass',
        ])
        ->assertOk();

    expect(Hash::check('newpass', $user->fresh()->password))->toBeTrue();
});

it('rejects password change with wrong old password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpass'),
    ]);

    $this->actingAs($user, 'web')
        ->postJson('/api/change-password/', [
            'user_id' => $user->id,
            'old_password' => 'wrong',
            'new_password' => 'newpass',
            'new_password_confirmation' => 'newpass',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['status' => 'invalid_old_password']);

    expect(Hash::check('oldpass', $user->fresh()->password))->toBeTrue();
});
