<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects answer updates with invalid id', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $answer = Answer::factory()->for($question)->create();

    $this->actingAs($owner, 'web')
        ->putJson("/api/answers/{$answer->id}", [
            'id' => 9999,
            'answer_text' => 'Updated',
            'sequence' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id']);
});
