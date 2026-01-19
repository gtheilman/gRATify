<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects answer updates when route id and payload id differ', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();

    $first = Answer::factory()->for($question)->create();
    $second = Answer::factory()->for($question)->create();

    $this->actingAs($owner, 'web')
        ->putJson("/api/answers/{$second->id}", [
            'id' => $first->id,
            'answer_text' => 'Mismatch update',
            'sequence' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id']);
});
