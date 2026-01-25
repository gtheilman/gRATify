<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns public presentation payload for the student client', function () {
    $assessment = Assessment::factory()->create([
        'password' => 'contract-pw',
        'active' => true,
    ]);
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['correct' => true, 'sequence' => 1]);
    $presentation = Presentation::factory()->for($assessment)->create(['user_id' => 'Student 1']);
    Attempt::factory()->for($presentation)->for($answer)->create();

    $this->getJson('/api/presentations/store/contract-pw/Student-1')
        ->assertCreated()
        ->assertJsonStructure([
            'id',
            'assessment_id',
            'user_id',
            'assessment' => [
                'id',
                'title',
                'questions' => [
                    '*' => ['id', 'stem', 'sequence', 'answers'],
                ],
            ],
            'attempts' => [
                '*' => ['id', 'answer_id', 'answer_correct', 'answer'],
            ],
        ]);
});
