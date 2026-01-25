<?php

use App\Models\Assessment;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question update not-found tests
 *
 * Ensures the API returns 404 and leaves data unchanged when the question
 * doesn't exist or doesn't belong to the current assessment context.
 */
uses(RefreshDatabase::class);

it('returns 404 when the question does not exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $missingId = $question->id + 999;

    $this->actingAs($owner)
        ->putJson("/api/questions/{$missingId}", [
            'id' => $missingId,
            'stem' => 'Should not update',
            'title' => 'Should not update',
        ])
        ->assertStatus(404);

    expect($question->fresh()->stem)->not->toBe('Should not update');
});

it('rejects updates when route id and payload id differ', function () {
    $owner = User::factory()->create();
    $firstAssessment = Assessment::factory()->for($owner, 'user')->create();
    $secondAssessment = Assessment::factory()->for($owner, 'user')->create();

    $questionInFirst = Question::factory()->for($firstAssessment)->create(['sequence' => 1]);
    $questionInSecond = Question::factory()->for($secondAssessment)->create(['sequence' => 1]);

    // Payload id is mismatched; validation should reject.
    $this->actingAs($owner)
        ->putJson("/api/questions/{$questionInSecond->id}", [
            'id' => $questionInFirst->id, // mismatched id
            'stem' => 'Updated via route id',
            'title' => 'Updated via route id',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id']);

    expect($questionInFirst->fresh()->stem)->not->toBe('Updated via route id');
    expect($questionInSecond->fresh()->stem)->not->toBe('Updated via route id');
});
