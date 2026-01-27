<?php

namespace App\Services\Scoring;

use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Builds a scored presentation model while preserving attempt order
 * and supporting legacy/plaintext user IDs.
 */
class PresentationScorer
{
    public function __construct(private ScoringManager $scoringManager)
    {
    }

    public function score(Presentation $presentation, Assessment $assessment, ?string $scheme = null): Presentation
    {
        $attemptsByQuestionId = $presentation->attempts
            ->filter(fn (Attempt $attempt): bool => $attempt->answer !== null)
            ->groupBy(fn (Attempt $attempt) => $attempt->answer->question_id);
        $appealsByQuestionId = $presentation->appeals->groupBy('question_id');

        // Attach ordered attempts per question so scoring schemes can honor attempt order.
        $questions = $assessment->questions->map(function (Question $question) use ($attemptsByQuestionId, $appealsByQuestionId) {
            $questionAttempts = $attemptsByQuestionId->get($question->id, collect())
                ->sortBy('created_at')
                ->values();

            $question->attempts = $questionAttempts;
            $question->setRelation('appeals', $appealsByQuestionId->get($question->id, collect()));

            return $question;
        });

        $assessment->setRelation('questions', $questions);
        $presentation->setRelation('assessment', $assessment);

        $scoring = $this->scoringManager
            ->forScheme($scheme ?? config('scoring.default', 'geometric-decay'))
            ->scoreQuestions($presentation->assessment->questions);

        foreach ($presentation->assessment->questions as $question) {
            $question->score = $scoring['questionScores'][$question->id] ?? 0;
        }
        $presentation->score = $scoring['total'];

        // Some older rows store a plaintext user_id; keep those working.
        try {
            $presentation->user_id = decrypt($presentation->user_id);
        } catch (DecryptException $e) {
            // Factories and legacy data may store plain IDs.
        }

        return $presentation;
    }
}
