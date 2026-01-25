<?php

use App\Services\Scoring\LinearDecayWithZerosScoring;
use Illuminate\Support\Collection;

it('drops to zero when the correct answer is the last possible choice', function () {
    $questions = new Collection([
        (object) [
            'id' => 1,
            'answers_count' => 4,
            'attempts' => [
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => true]],
            ],
        ],
    ]);

    $scoring = new LinearDecayWithZerosScoring();
    $result = $scoring->scoreQuestions($questions);

    expect($result['questionScores'][1])->toBe(0);
    expect($result['total'])->toBe(0.0);
});

it('uses linear decay when the correct answer is not last', function () {
    $questions = new Collection([
        (object) [
            'id' => 2,
            'answers_count' => 5,
            'attempts' => [
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => true]],
            ],
        ],
    ]);

    $scoring = new LinearDecayWithZerosScoring();
    $result = $scoring->scoreQuestions($questions);

    expect($result['questionScores'][2])->toBe(80);
    expect($result['total'])->toBe(80.0);
});
