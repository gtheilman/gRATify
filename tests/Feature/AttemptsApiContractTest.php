<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buildPresentation(): array
{
    $assessment = Assessment::factory()->create(['password' => 'pw']);
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    return [$presentation, $answer];
}

it('enforces required fields', function () {
    $this->postJson('/api/attempts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['presentation_id', 'answer_id']);
});

it('returns not found when ids are invalid', function () {
    $this->postJson('/api/attempts', [
        'presentation_id' => 999,
        'answer_id' => 888,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['presentation_id', 'answer_id']);
});

it('returns alreadyAttempted on duplicate submissions', function () {
    [$presentation, $answer] = buildPresentation();

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
    ])
        ->assertCreated()
        ->assertJson([
            'correct' => true,
            'alreadyAttempted' => false,
        ]);

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
    ])
        ->assertOk()
        ->assertJson([
            'correct' => false,
            'alreadyAttempted' => true,
        ]);
});
