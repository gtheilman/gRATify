<?php

namespace App\Services\Scoring;

use Illuminate\Support\Collection;

/**
 * Halves the question score for each incorrect attempt (geometric decay).
 */
class GeometricDecayScoring implements ScoringStrategy
{
    public function scoreQuestions(Collection $questions): array
    {
        $questionScores = [];
        $total = 0;
        $count = 0;

        foreach ($questions as $question) {
            $score = 100;
            $answeredCorrectly = false;

            foreach ($question->attempts as $attempt) {
                if ($attempt->answer->correct == 1) {
                    $answeredCorrectly = true;
                } else {
                    // Incorrect attempt halves remaining score.
                    $score = $score / 2;
                }
            }

            if (! $answeredCorrectly) {
                $score = 0;
            }

            $questionScores[$question->id] = $score;
            $total += $score;
            $count++;
        }

        $average = $count > 0 ? round(($total / $count), 1) : 0.0;

        return [
            'questionScores' => $questionScores,
            'total' => $average,
        ];
    }
}
