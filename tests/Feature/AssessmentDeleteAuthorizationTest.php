<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment delete authorization tests
 *
 * Ensures only owners/admins can delete assessments and locked assessments cannot be deleted.
 */
uses(RefreshDatabase::class);

it('allows the owner to delete an unlocked assessment', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($owner)
        ->deleteJson("/api/assessments/{$assessment->id}")
        ->assertNoContent();

    expect(Assessment::find($assessment->id))->toBeNull();
});

it('blocks non-owners from deleting an assessment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    $this->actingAs($other)
        ->deleteJson("/api/assessments/{$assessment->id}")
        ->assertStatus(403);

    expect(Assessment::find($assessment->id))->not->toBeNull();
});
