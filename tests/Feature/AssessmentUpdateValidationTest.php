<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment update validation tests
 *
 * Ensures required fields are enforced on update.
 */
uses(RefreshDatabase::class);

it('rejects updates without a title', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create(['title' => 'Original']);

    $this->actingAs($owner, 'web')
        ->putJson("/api/assessments/{$assessment->id}", [
            // title missing
        ])
        ->assertStatus(422);

    expect($assessment->fresh()->title)->toBe('Original');
});
