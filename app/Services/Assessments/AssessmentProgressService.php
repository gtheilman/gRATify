<?php

namespace App\Services\Assessments;

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\Question;

/**
 * Assembles assessment progress with decrypted group labels when possible.
 */
class AssessmentProgressService
{
    public function build(int $assessmentId): Assessment
    {
        $assessment = Assessment::with('questions.answers')->findOrFail($assessmentId);

        $assessment->setRelation(
            'questions',
            $assessment->questions->sortBy(fn (Question $question) => $question->sequence)->values()
        );

        $presentations = Presentation::with('attempts')
            ->where('assessment_id', $assessmentId)
            ->get()
            ->map(function ($presentation) {
                // Legacy rows or factories may already store plaintext IDs.
                try {
                    $presentation->group_label = decrypt($presentation->user_id);
                } catch (\Throwable $e) {
                    $presentation->group_label = $presentation->user_id;
                }

                return $presentation;
            });

        $assessment->setRelation('presentations', $presentations);

        return $assessment;
    }
}
