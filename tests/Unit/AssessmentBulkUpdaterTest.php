<?php

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\Question;
use App\Services\Assessments\AssessmentBulkUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('updates assessment fields and upserts questions/answers', function () {
    $assessment = Assessment::factory()->create([
        'title' => 'Old Title',
        'course' => 'Old Course',
        'memo' => 'Old Memo',
        'penalty_method' => 'none',
        'active' => false,
        'scheduled_at' => null,
    ]);

    $question = Question::factory()->for($assessment)->create([
        'title' => 'Old Question',
        'stem' => 'Old Stem',
        'sequence' => 1,
    ]);

    $answer = Answer::factory()->for($question)->create([
        'answer_text' => 'Old Answer',
        'sequence' => 1,
        'correct' => false,
    ]);

    $assessmentData = [
        'title' => 'New Title',
        'time_limit' => 15,
        'course' => 'New Course',
        'memo' => 'New Memo',
        'penalty_method' => 'linear',
        'active' => true,
        'scheduled_at' => '2026-02-01T10:30:00.000Z',
    ];

    $questionPayloads = [
        [
            'id' => $question->id,
            'title' => 'Updated Question',
            'stem' => 'Updated Stem',
            'sequence' => 2,
            'answers' => [
                [
                    'id' => $answer->id,
                    'answer_text' => 'Updated Answer',
                    'sequence' => 2,
                    'correct' => true,
                ],
            ],
        ],
    ];

    $service = new AssessmentBulkUpdater();
    $service->update($assessment, $assessmentData, $questionPayloads);

    $assessment->refresh();
    $question->refresh();
    $answer->refresh();

    expect($assessment->title)->toBe('New Title')
        ->and($assessment->course)->toBe('New Course')
        ->and($assessment->memo)->toBe('New Memo')
        ->and($assessment->penalty_method)->toBe('linear')
        ->and($assessment->active)->toBeTruthy()
        ->and((string) $assessment->scheduled_at)->toContain('2026-02-01');

    expect($question->title)->toBe('Updated Question')
        ->and($question->stem)->toBe('Updated Stem')
        ->and($question->sequence)->toBe(2);

    expect($answer->answer_text)->toBe('Updated Answer')
        ->and($answer->sequence)->toBe(2)
        ->and((bool) $answer->correct)->toBeTrue();
});

it('does not create extra questions or answers during update', function () {
    $assessment = Assessment::factory()->create();
    $question = Question::factory()->for($assessment)->create(['sequence' => 1]);
    $answer = Answer::factory()->for($question)->create(['sequence' => 1]);

    $service = new AssessmentBulkUpdater();
    $service->update($assessment, [
        'title' => $assessment->title,
    ], [
        [
            'id' => $question->id,
            'stem' => $question->stem,
            'sequence' => 1,
            'answers' => [
                [
                    'id' => $answer->id,
                    'answer_text' => $answer->answer_text,
                    'sequence' => 1,
                    'correct' => $answer->correct,
                ],
            ],
        ],
    ]);

    expect(Question::count())->toBe(1)
        ->and(Answer::count())->toBe(1);
});
