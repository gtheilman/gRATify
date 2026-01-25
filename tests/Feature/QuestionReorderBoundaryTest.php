<?php

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not change order when promoting the first question', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $first = Question::factory()->for($assessment)->create(['sequence' => 1, 'title' => 'First']);
    $second = Question::factory()->for($assessment)->create(['sequence' => 2, 'title' => 'Second']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/promote', [
            'question_id' => $first->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertOk();

    $order = Question::whereIn('id', [$first->id, $second->id])
        ->orderBy('sequence')
        ->pluck('title')
        ->all();

    expect($order)->toBe(['First', 'Second']);
});

it('does not change order when demoting the last question', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $first = Question::factory()->for($assessment)->create(['sequence' => 1, 'title' => 'First']);
    $second = Question::factory()->for($assessment)->create(['sequence' => 2, 'title' => 'Second']);

    $this->actingAs($owner, 'web')
        ->postJson('/api/questions/demote', [
            'question_id' => $second->id,
            'assessment_id' => $assessment->id,
        ])
        ->assertOk();

    $order = Question::whereIn('id', [$first->id, $second->id])
        ->orderBy('sequence')
        ->pluck('title')
        ->all();

    expect($order)->toBe(['First', 'Second']);
});
