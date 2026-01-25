<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not change order when promoting the first answer', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $first = Answer::factory()->for($question)->create(['sequence' => 1, 'answer_text' => 'First']);
    $second = Answer::factory()->for($question)->create(['sequence' => 2, 'answer_text' => 'Second']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/promote', [
            'answer_id' => $first->id,
            'question_id' => $question->id,
        ])
        ->assertOk();

    $order = Answer::whereIn('id', [$first->id, $second->id])
        ->orderBy('sequence')
        ->pluck('answer_text')
        ->all();

    expect($order)->toBe(['First', 'Second']);
});

it('does not change order when demoting the last answer', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create();
    $first = Answer::factory()->for($question)->create(['sequence' => 1, 'answer_text' => 'First']);
    $second = Answer::factory()->for($question)->create(['sequence' => 2, 'answer_text' => 'Second']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/demote', [
            'answer_id' => $second->id,
            'question_id' => $question->id,
        ])
        ->assertOk();

    $order = Answer::whereIn('id', [$first->id, $second->id])
        ->orderBy('sequence')
        ->pluck('answer_text')
        ->all();

    expect($order)->toBe(['First', 'Second']);
});
