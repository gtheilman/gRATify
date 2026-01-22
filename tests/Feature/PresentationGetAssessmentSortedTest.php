<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns getAssessment payload with questions and answers sorted by sequence', function () {
    $assessment = Assessment::factory()->create();

    $q2 = Question::factory()->for($assessment)->create([
        'sequence' => 2,
        'stem' => 'Second',
    ]);
    $q1 = Question::factory()->for($assessment)->create([
        'sequence' => 1,
        'stem' => 'First',
    ]);

    Answer::factory()->for($q1)->create(['sequence' => 2, 'answer_text' => 'B']);
    Answer::factory()->for($q1)->create(['sequence' => 1, 'answer_text' => 'A']);

    $presentation = Presentation::factory()->create([
        'assessment_id' => $assessment->id,
        'user_id' => 'student1',
    ]);

    $response = $this->getJson("/api/presentations/getAssessment/{$presentation->id}")
        ->assertOk()
        ->json();

    $questions = data_get($response, 'questions');
    expect($questions)->toHaveCount(2);
    expect(data_get($questions, '0.sequence'))->toBe(1);
    expect(data_get($questions, '0.stem'))->toBe('First');

    $answers = data_get($questions, '0.answers');
    expect($answers)->toHaveCount(2);
    expect(data_get($answers, '0.sequence'))->toBe(1);
    expect(data_get($answers, '0.answer_text'))->toBe('A');
});
