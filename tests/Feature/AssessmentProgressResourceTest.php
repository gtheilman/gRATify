<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns structured assessment progress resource', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Progress Assessment',
        'active' => true,
    ]);
    $question = Question::factory()->for($assessment)->create([
        'sequence' => 1,
        'stem' => 'Question 1',
    ]);
    Answer::factory()->for($question)->create([
        'sequence' => 1,
        'answer_text' => 'Answer 1',
        'correct' => true,
    ]);
    Presentation::factory()->for($assessment)->create([
        'user_id' => 'Group A',
    ]);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->assertJsonPath('id', $assessment->id)
        ->assertJsonPath('title', 'Progress Assessment')
        ->assertJsonPath('questions.0.stem', 'Question 1')
        ->assertJsonPath('questions.0.answers.0.answer_text', 'Answer 1')
        ->assertJsonPath('presentations.0.group_label', 'Group A');
});
