<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Minimal smoke test for create + edit flow to catch regressions during migration.
 */
uses(RefreshDatabase::class);

it('allows a user to create an assessment and add a question', function () {
    $owner = User::factory()->create();

    // Create assessment
    $assessmentId = $this->actingAs($owner, 'web')
        ->postJson('/api/assessments', [
            'title' => 'Smoke Assessment',
            'active' => true,
        ])
        ->assertCreated()
        ->json('id');

    expect($assessmentId)->not->toBeNull();

    // Add a question
    $questionId = $this->actingAs($owner, 'web')
        ->postJson('/api/questions', [
            'title' => 'Q1',
            'stem' => 'Stem 1',
            'assessment_id' => $assessmentId,
        ])
        ->assertCreated()
        ->json('id');

    expect($questionId)->not->toBeNull();
    expect(Assessment::find($assessmentId))->not->toBeNull();
    expect(Question::find($questionId))->not->toBeNull();
});
