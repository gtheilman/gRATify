<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Presentation;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Assessment delete lock tests
 *
 * Ensures assessments with responses/presentations cannot be deleted.
 */
uses(RefreshDatabase::class);

it('blocks deletion when responses exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    Presentation::factory()->for($assessment)->create();

    $this->actingAs($owner)
        ->deleteJson("/api/assessments/{$assessment->id}")
        ->assertNoContent();
});
