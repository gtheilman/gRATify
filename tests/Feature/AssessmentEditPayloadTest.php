<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns edit payload with required question and answer fields', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner, 'user')->create([
        'title' => 'Edit Payload Test',
    ]);
    $question = Question::factory()->for($assessment)->create([
        'sequence' => 1,
        'stem' => 'Question 1',
        'title' => 'Title 1',
    ]);
    Answer::factory()->for($question)->create([
        'sequence' => 1,
        'answer_text' => 'Answer 1',
        'correct' => true,
    ]);

    $response = $this->actingAs($owner, 'web')
        ->getJson("/api/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->json();

    expect(data_get($response, 'questions.0.title'))->toBe('Title 1');
    expect(data_get($response, 'questions.0.stem'))->toBe('Question 1');
    expect(data_get($response, 'questions.0.sequence'))->toBe(1);
    expect(data_get($response, 'questions.0.answers.0.answer_text'))->toBe('Answer 1');
    expect((bool) data_get($response, 'questions.0.answers.0.correct'))->toBeTrue();
    expect(data_get($response, 'questions.0.answers.0.sequence'))->toBe(1);
});
