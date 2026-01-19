<?php

use App\Services\Scoring\LinearDecayScoring;
use Illuminate\Support\Collection;

it('scores with linear decay per wrong attempt and averages total', function () {
    $questions = collect([
        (object) [
            'id' => 1,
            'attempts' => collect([
                (object) ['answer' => (object) ['correct' => 0]],
                (object) ['answer' => (object) ['correct' => 0]],
                (object) ['answer' => (object) ['correct' => 1]],
            ]),
        ],
        (object) [
            'id' => 2,
            'attempts' => collect([
                (object) ['answer' => (object) ['correct' => 0]],
                (object) ['answer' => (object) ['correct' => 0]],
            ]),
        ],
    ]);

    $scoring = (new LinearDecayScoring(step: 25))->scoreQuestions($questions);

    expect($scoring['questionScores'][1])->toBe(50)
        ->and($scoring['questionScores'][2])->toBe(0)
        ->and($scoring['total'])->toBe(25.0);
});
