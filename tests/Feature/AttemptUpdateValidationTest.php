<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects attempt updates with invalid ids', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();
    $presentation = Presentation::factory()->for($assessment)->create();
    $attempt = Attempt::factory()->for($presentation)->for($answer)->create();

    $this->actingAs($owner, 'web')
        ->putJson("/api/attempts/{$attempt->id}", [
            'id' => 9999,
            'presentation_id' => $presentation->id,
            'answer_id' => $answer->id,
            'points' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id']);
});
