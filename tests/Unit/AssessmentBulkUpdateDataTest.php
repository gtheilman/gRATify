<?php

use App\DTO\AssessmentBulkUpdateData;
use Tests\TestCase;

uses(TestCase::class);

it('collects question and answer ids from validated payloads', function () {
    $validated = [
        'assessment' => ['title' => 'Assessment'],
        'questions' => [
            [
                'id' => 10,
                'stem' => 'Q1',
                'sequence' => 1,
                'answers' => [
                    ['id' => 100, 'answer_text' => 'A1', 'sequence' => 1],
                    ['id' => 101, 'answer_text' => 'A2', 'sequence' => 2],
                ],
            ],
            [
                'id' => 11,
                'stem' => 'Q2',
                'sequence' => 2,
                'answers' => [],
            ],
        ],
    ];

    $dto = AssessmentBulkUpdateData::fromValidated($validated);

    expect($dto->questionIds())->toBe([10, 11]);
    expect($dto->answerIds())->toBe([100, 101]);
    expect($dto->answerPayloads())->toHaveCount(2);
});
