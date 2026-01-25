<?php

namespace App\Services\Scoring;

use Illuminate\Support\Collection;

/**
 * Subtracts a fixed fraction of 100 based on answer count (linear decay).
 */
class LinearDecayScoring implements ScoringStrategy
{
    public function scoreQuestions(Collection $questions): array
    {
        $questionScores = [];
        $total = 0;
        $count = 0;

        foreach ($questions as $question) {
            $answerCount = $question->answers_count ?? null;
            if ($answerCount === null && isset($question->answers)) {
                $answerCount = is_countable($question->answers) ? count($question->answers) : null;
            }
            $answerCount = max(1, (int) ($answerCount ?? 1));
            $step = 100 / $answerCount;
            $score = 100;
            $answeredCorrectly = false;

            foreach ($question->attempts as $attempt) {
                if ($attempt->answer->correct == 1) {
                    $answeredCorrectly = true;
                    break;
                }
                // Penalize by a fixed fraction based on total answer count.
                $score = max(0, $score - $step);
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
