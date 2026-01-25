<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment update authorization tests
 *
 * Ensures unauthenticated or non-owner updates are rejected.
 */
uses(RefreshDatabase::class);

it('returns 401 when unauthenticated users try to update an assessment', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Original']);

    $this->putJson("/api/assessments/{$assessment->id}", [
        'title' => 'New Title',
    ])->assertStatus(401);

    expect($assessment->fresh()->title)->toBe('Original');
});

it('returns 403 when a non-owner tries to update an assessment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Original']);

    $this->actingAs($other, 'web')
        ->putJson("/api/assessments/{$assessment->id}", [
            'title' => 'New Title',
        ])
        ->assertStatus(403);

    expect($assessment->fresh()->title)->toBe('Original');
});
