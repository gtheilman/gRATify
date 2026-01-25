<?php

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment active toggle test
 *
 * Ensures toggling active state persists.
 */
uses(RefreshDatabase::class);

it('persists active toggle changes', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create(['active' => true]);

    $this->actingAs($owner)
        ->putJson("/api/assessments/{$assessment->id}", [
            'title' => $assessment->title,
            'active' => false,
        ])
        ->assertStatus(200);

    expect((bool) $assessment->fresh()->active)->toBeFalse();
});
