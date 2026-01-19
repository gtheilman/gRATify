<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Guards the edit endpoint used by the browser SPA.
 */
uses(RefreshDatabase::class);

it('returns 401 when fetching edit data unauthenticated', function () {
    $assessment = Assessment::factory()->create();

    $this->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertStatus(401);
});

it('returns assessment with questions/answers for owner via edit endpoint', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['sequence' => 1, 'correct' => true]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->assertJsonFragment(['id' => $assessment->id])
        ->assertJsonFragment(['id' => $question->id])
        ->assertJsonFragment(['correct' => 1]);
});
