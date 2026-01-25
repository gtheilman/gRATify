<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * End-to-end happy path: create assessment -> questions/answers -> mark correct -> create presentation/attempt -> lock behavior.
 */
uses(RefreshDatabase::class);

it('runs the full assessment flow', function () {
    $owner = User::factory()->create();

    // Create assessment
    $assessmentId = $this->actingAs($owner, 'web')
        ->postJson('/api/assessments', [
            'title' => 'E2E Assessment',
            'active' => true,
        ])
        ->assertCreated()
        ->json('id');

    // Add question
    $questionId = $this->actingAs($owner, 'web')
        ->postJson('/api/questions', [
            'title' => 'Q1',
            'stem' => 'Stem 1',
            'assessment_id' => $assessmentId,
        ])
        ->assertCreated()
        ->json('id');

    // Add answers
    $a1 = $this->actingAs($owner, 'web')
        ->postJson('/api/answers', [
            'answer_text' => 'Answer A',
            'question_id' => $questionId,
            'sequence' => 1,
            'correct' => false,
        ])
        ->assertCreated()
        ->json('id');

    $a2 = $this->actingAs($owner, 'web')
        ->postJson('/api/answers', [
            'answer_text' => 'Answer B',
            'question_id' => $questionId,
            'sequence' => 2,
            'correct' => true,
        ])
        ->assertCreated()
        ->json('id');

    // Mark correct answer (ensure only one is correct)
    $this->actingAs($owner, 'web')
        ->putJson("/api/answers/{$a2}", [
            'id' => $a2,
            'answer_text' => 'Answer B',
            'correct' => true,
            'sequence' => 2,
        ])
        ->assertStatus(200);

    // Create presentation and attempt correct answer
    $presentation = Presentation::factory()->for(Assessment::find($assessmentId))->create();
    Attempt::factory()->for($presentation)->for(Answer::find($a2))->create([
        'points' => 10,
    ]);

    // Verify lock: question update now blocked
    $this->actingAs($owner, 'web')
        ->putJson("/api/questions/{$questionId}", [
            'id' => $questionId,
            'stem' => 'Updated stem',
            'title' => 'Q1 updated',
        ])
        ->assertStatus(403);

    // Scoring: ensure attempt points persisted
    $attempt = Attempt::first();
    expect($attempt->points)->toBe(10.0);
});
