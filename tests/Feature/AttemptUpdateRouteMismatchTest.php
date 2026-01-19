<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects attempt updates when route id and payload id differ', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();
    $presentation = Presentation::factory()->for($assessment)->create();

    $first = Attempt::factory()->for($presentation)->for($answer)->create();
    $second = Attempt::factory()->for($presentation)->for($answer)->create();

    $this->actingAs($owner, 'web')
        ->putJson("/api/attempts/{$second->id}", [
            'id' => $first->id,
            'presentation_id' => $presentation->id,
            'answer_id' => $answer->id,
            'points' => 3,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id']);
});
