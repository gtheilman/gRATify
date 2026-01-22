<?php

use App\Services\Scoring\LinearDecayScoring;
use Illuminate\Support\Collection;

it('scores based on total answer count with linear decay', function () {
    $questions = new Collection([
        (object) [
            'id' => 1,
            'answers_count' => 5,
            'attempts' => [
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => true]],
            ],
        ],
        (object) [
            'id' => 2,
            'answers_count' => 10,
            'attempts' => [
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => false]],
                (object) ['answer' => (object) ['correct' => true]],
            ],
        ],
    ]);

    $scoring = new LinearDecayScoring();
    $result = $scoring->scoreQuestions($questions);

    expect($result['questionScores'][1])->toBe(80);
    expect($result['questionScores'][2])->toBe(80);
    expect($result['total'])->toBe(80.0);
});
