<?php

use App\Models\Assessment;
use App\Models\Answer;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns assessment edit payload with questions, answers, and presentations', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Contract Edit',
    ]);
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['sequence' => 1, 'correct' => true]);
    Presentation::factory()->for($assessment)->create(['user_id' => 'Group X']);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->assertJsonPath('id', $assessment->id)
        ->assertJsonStructure([
            'id',
            'title',
            'questions' => [
                '*' => ['id', 'stem', 'sequence', 'answers'],
            ],
            'presentations' => [
                '*' => ['id', 'group_label', 'attempts'],
            ],
        ])
        ->assertJsonPath('presentations.0.group_label', 'Group X');
});

it('returns assessment progress payload used by progress/feedback views', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Contract Progress',
    ]);
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    Answer::factory()->for($question)->create(['sequence' => 1]);
    Presentation::factory()->for($assessment)->create(['user_id' => 'Group Y']);

    $this->actingAs($owner, 'web')
        ->getJson("/api/assessment/attempts/{$assessment->id}")
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'title',
            'questions' => [
                '*' => ['id', 'stem', 'answers'],
            ],
            'presentations' => [
                '*' => ['id', 'group_label', 'attempts'],
            ],
        ]);
});
