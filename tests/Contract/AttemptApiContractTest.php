<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns attempt store payload with correct flags', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
        'points' => 5.0,
    ])
        ->assertCreated()
        ->assertJsonStructure(['correct', 'alreadyAttempted'])
        ->assertJson([
            'correct' => true,
            'alreadyAttempted' => false,
        ]);
});

it('returns duplicate attempt payload with alreadyAttempted true', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => false]);

    Attempt::factory()->for($presentation)->for($answer)->create();

    $this->postJson('/api/attempts', [
        'presentation_id' => $presentation->id,
        'answer_id' => $answer->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['correct', 'alreadyAttempted'])
        ->assertJson([
            'correct' => false,
            'alreadyAttempted' => true,
        ]);
});
