<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Regression coverage for admin-only routes and behaviors.
 * These ensure we don't silently lose admin access and surface "no users found" again.
 */
uses(RefreshDatabase::class);

function makeAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('requires admin role to list users', function () {
    $admin = makeAdmin();
    $regular = User::factory()->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/user-management/users')
        ->assertOk()
        ->assertJsonFragment(['id' => $admin->id]);

    $this->actingAs($regular, 'web')
        ->getJson('/api/user-management/users')
        ->assertStatus(403);
});

it('restricts admin change password to admins', function () {
    $admin = makeAdmin();
    $target = User::factory()->create(['password' => Hash::make('oldpass')]);
    $regular = User::factory()->create();

    // Admin can change someone else’s password.
    $this->actingAs($admin, 'web')
        ->postJson('/api/user-management/users/admin-change-password/', [
            'user_id' => $target->id,
            'new_password' => 'newpass',
            'new_password_confirmation' => 'newpass',
        ])
        ->assertOk();

    expect(Hash::check('newpass', $target->fresh()->password))->toBeTrue();

    // Non-admin is blocked.
    $this->actingAs($regular, 'web')
        ->postJson('/api/user-management/users/admin-change-password/', [
            'user_id' => $target->id,
            'new_password' => 'another',
            'new_password_confirmation' => 'another',
        ])
        ->assertStatus(403);
});

it('restricts register user and delete user to admins', function () {
    $admin = makeAdmin();
    $regular = User::factory()->create();
    $ownerWithAssessments = User::factory()->create();
    $assessment = \App\Models\Assessment::factory()->for($ownerWithAssessments, 'user')->create();

    // Admin can register a user.
    $this->actingAs($admin, 'web')
        ->postJson('/api/user-management/users/register-user', [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password',
            'role' => 'editor',
        ])
        ->assertCreated()
        ->assertJsonFragment(['status' => 'ok']);

    $created = User::where('username', 'newuser')->first();
    expect($created)->not->toBeNull();

    // Non-admin blocked from registering.
    $this->actingAs($regular, 'web')
        ->postJson('/api/user-management/users/register-user', [
            'username' => 'blocked',
            'email' => 'blocked@example.com',
            'password' => 'password',
        ])
        ->assertStatus(403);

    // Admin can delete; non-admin cannot.
    $this->actingAs($admin, 'web')
        ->deleteJson("/api/user-management/users/{$created->id}")
        ->assertNoContent();

    $another = User::factory()->create();
    $this->actingAs($regular, 'web')
        ->deleteJson("/api/user-management/users/{$another->id}")
        ->assertStatus(403);

    // Admin cannot delete users who own assessments.
    $this->actingAs($admin, 'web')
        ->deleteJson("/api/user-management/users/{$ownerWithAssessments->id}")
        ->assertStatus(409)
        ->assertJsonFragment(['message' => 'Cannot delete users with assessments. Reassign or delete their assessments first.']);

    expect(\App\Models\User::find($ownerWithAssessments->id))->not->toBeNull();
    expect(\App\Models\Assessment::find($assessment->id))->not->toBeNull();
});

it('restricts presentations completed listing to admins', function () {
    $admin = makeAdmin();
    $regular = User::factory()->create();

    $this->actingAs($admin, 'web')
        ->getJson('/api/presentations/completed')
        ->assertOk();

    $this->actingAs($regular, 'web')
        ->getJson('/api/presentations/completed')
        ->assertStatus(403);
});
