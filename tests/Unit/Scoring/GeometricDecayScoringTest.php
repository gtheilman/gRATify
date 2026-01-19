<?php

use App\Services\Scoring\GeometricDecayScoring;
use Illuminate\Support\Collection;

it('scores with geometric decay per question and averages total', function () {
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

    $scoring = (new GeometricDecayScoring())->scoreQuestions($questions);

    expect($scoring['questionScores'][1])->toBe(25)
        ->and($scoring['questionScores'][2])->toBe(0)
        ->and($scoring['total'])->toBe(12.5);
});
