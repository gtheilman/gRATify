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
        $assessment = Assessment::select(['id', 'title', 'course', 'active', 'short_url'])
            ->with(['questions' => function ($query) {
                $query->select(['id', 'assessment_id', 'stem', 'sequence'])
                    ->orderBy('sequence')
                    ->with(['answers' => function ($answerQuery) {
                        $answerQuery->select(['id', 'question_id', 'answer_text', 'correct', 'sequence']);
                    }]);
            }])
            ->findOrFail($assessmentId);

        $presentations = Presentation::select(['id', 'assessment_id', 'user_id', 'created_at', 'updated_at'])
            ->with(['attempts' => function ($query) {
                $query->select(['id', 'presentation_id', 'answer_id', 'points', 'created_at', 'updated_at']);
                $query->orderBy('created_at');
            }])
            ->where('assessment_id', $assessmentId)
            ->orderBy('created_at')
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
