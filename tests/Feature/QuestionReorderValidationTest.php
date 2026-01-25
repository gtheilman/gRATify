<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects promote with missing ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/api/questions/promote', [])
        ->assertStatus(422);
});

it('rejects demote with invalid ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/api/questions/demote', [
            'question_id' => 9999,
            'assessment_id' => 9999,
        ])
        ->assertStatus(422);
});

it('rejects reorder when question does not belong to assessment', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $otherAssessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($otherAssessment)->create(['sequence' => 1]);

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/promote', [
            'question_id' => $question->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['assessment_id']);
});

it('allows reorder when ids exist', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $q1 = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $q2 = Question::factory()->for($assessment)->create(['sequence' => 2]);

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/demote', [
            'question_id' => $q1->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['status' => 'Renumbered']);

    $order = Question::whereIn('id', [$q1->id, $q2->id])
        ->orderBy('sequence')
        ->pluck('id')
        ->all();

    expect($order)->toBe([$q2->id, $q1->id]);
});
