<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns debug payload on attempt store when requested', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    $this->withHeader('X-Debug', '1')
        ->postJson('/api/attempts', [
            'presentation_id' => $presentation->id,
            'answer_id' => $answer->id,
        ])
        ->assertCreated()
        ->assertJsonStructure(['correct', 'alreadyAttempted', 'debug' => ['server_ms', 'db_ms', 'queries']]);
});

it('returns debug payload on bulk attempt store when requested', function () {
    $assessment = Assessment::factory()->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create(['correct' => true]);

    $this->withHeader('X-Debug', '1')
        ->postJson('/api/attempts/bulk', [
            'attempts' => [
                ['presentation_id' => $presentation->id, 'answer_id' => $answer->id],
            ],
        ])
        ->assertOk()
        ->assertJsonStructure(['results', 'debug' => ['server_ms', 'db_ms', 'queries']]);
});
