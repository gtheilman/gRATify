<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects answer promote with missing ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/api/answers/promote', [])
        ->assertStatus(422);
});

it('rejects answer demote with invalid ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/api/answers/demote', [
            'answer_id' => 9999,
            'question_id' => 9999,
        ])
        ->assertStatus(422);
});

it('rejects reorder when answer does not belong to question', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $otherQuestion = Question::factory()->for($assessment)->create(['sequence' => 2]);
    $answer = Answer::factory()->for($otherQuestion)->create(['sequence' => 1]);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/promote', [
            'answer_id' => $answer->id,
            'question_id' => $question->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['question_id']);
});

it('allows answer reorder when ids exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);

    $a1 = Answer::factory()->for($question)->create(['sequence' => 1]);
    $a2 = Answer::factory()->for($question)->create(['sequence' => 2]);

    $this->actingAs($owner, 'web')
        ->postJson('/api/answers/demote', [
            'answer_id' => $a1->id,
            'question_id' => $question->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['status' => 'Renumbered']);

    $order = Answer::whereIn('id', [$a1->id, $a2->id])
        ->orderBy('sequence')
        ->pluck('id')
        ->all();

    expect($order)->toBe([$a2->id, $a1->id]);
});
