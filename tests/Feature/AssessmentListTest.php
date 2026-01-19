<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Basic listing behavior for assessments to guard future route/auth changes.
 */
uses(RefreshDatabase::class);

it('requires auth to list assessments (now protected)', function () {
    $this->getJson('/api/assessments')->assertStatus(401);
});

it('returns only the owners assessments for non-admin users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Assessment::factory()->for($user1, 'user')->create(['title' => 'Mine']);
    Assessment::factory()->for($user2, 'user')->create(['title' => 'Theirs']);

    $this->actingAs($user1, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->assertJsonMissing(['title' => 'Theirs'])
        ->assertJsonFragment(['title' => 'Mine']);
});

it('returns all assessments for admin role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Assessment::factory()->for($user1, 'user')->create(['title' => 'First']);
    Assessment::factory()->for($user2, 'user')->create(['title' => 'Second']);

    $this->actingAs($admin, 'web')
        ->getJson('/api/assessments')
        ->assertOk()
        ->assertJsonFragment(['title' => 'First'])
        ->assertJsonFragment(['title' => 'Second']);
});

it('allows admin role to list assessments for a given user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    Assessment::factory()->for($owner, 'user')->create(['title' => 'Owner Only']);

    $this->actingAs($admin, 'web')
        ->getJson("/api/list-assessments-by-user/{$owner->id}")
        ->assertOk()
        ->assertJsonFragment(['title' => 'Owner Only']);
});
