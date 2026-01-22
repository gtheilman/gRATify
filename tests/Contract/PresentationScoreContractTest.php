<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns scored presentation payload with assessment question scores', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['correct' => true]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Team A']);
    Attempt::factory()->for($presentation)->for($answer)->create();

    $this->actingAs($owner, 'web')
        ->getJson("/api/presentations/score-by-assessment-id/{$assessment->id}")
        ->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'assessment_id',
                'user_id',
                'score',
                'assessment' => [
                    'id',
                    'title',
                    'questions' => [
                        '*' => ['id', 'sequence', 'score', 'attempts'],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('0.assessment.questions.0.attempts.0.answer_id', $answer->id);
});
