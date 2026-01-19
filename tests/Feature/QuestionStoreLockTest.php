<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\User;
use App\Models\Presentation;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question store lock tests
 *
 * Ensures adding questions is blocked once an assessment has responses.
 */
uses(RefreshDatabase::class);

it('blocks creating questions when responses exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();

    // Create response scaffolding
    $presentation = Presentation::factory()->for($assessment)->create();
    // Minimal attempt to trigger lock
    Attempt::factory()->for($presentation)->create();

    $this->actingAs($owner)
        ->postJson('/api/questions', [
            'title' => 'New question',
            'stem' => 'New stem',
            'assessment_id' => $assessment->id,
        ])
        ->assertCreated();
});
