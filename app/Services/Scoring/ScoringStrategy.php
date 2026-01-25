<?php

namespace App\Services\Scoring;

use Illuminate\Support\Collection;

/**
 * Contract for scoring implementations used by the scoring manager.
 */
interface ScoringStrategy
{
    /**
        * Score a collection of questions (each containing an attempts collection).
        *
        * @param Collection<int, mixed> $questions
        * @return array{questionScores: array<int, float>, total: float}
        */
    public function scoreQuestions(Collection $questions): array;
}
