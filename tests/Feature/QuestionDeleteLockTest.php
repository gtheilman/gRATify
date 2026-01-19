<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\User;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Question delete lock tests
 *
 * Ensures deletions are blocked once an assessment has responses.
 */
uses(RefreshDatabase::class);

it('blocks deletes when an assessment has responses', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);

    $presentation = Presentation::factory()->for($assessment)->create();
    Attempt::factory()->for($presentation)->for($answer)->create();

    $this->actingAs($owner)
        ->deleteJson("/api/questions/{$question->id}")
        ->assertStatus(403);

    expect(Question::find($question->id))->not->toBeNull();
});

it('allows deletes when there are no responses', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $this->actingAs($owner)
        ->deleteJson("/api/questions/{$question->id}")
        ->assertNoContent();

    expect(Question::find($question->id))->toBeNull();
});
