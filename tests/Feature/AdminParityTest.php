<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to update assessments they do not own', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $assessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Original']);

    $this->actingAs($admin, 'web')
        ->putJson("/api/assessments/{$assessment->id}", [
            'title' => 'Admin Edited',
        ])
        ->assertOk();

    expect($assessment->fresh()->title)->toBe('Admin Edited');
});

it('allows admin to delete assessments they do not own', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($admin, 'web')
        ->deleteJson("/api/assessments/{$assessment->id}")
        ->assertNoContent();

    expect(Assessment::find($assessment->id))->toBeNull();
});

it('allows admin to view attempts for any assessment', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($admin, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk();
});
