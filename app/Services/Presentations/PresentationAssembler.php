<?php

namespace App\Services\Presentations;

use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Appeal;
use App\Models\Presentation;

/**
 * Builds a public presentation response while reusing existing attempts.
 */
class PresentationAssembler
{
    /**
     * @return array{presentation: Presentation, created: bool}
     */
    public function assemblePublic(Assessment $assessment, string $userId): array
    {
        $created = false;
        $presentation = Presentation::select(['id', 'assessment_id', 'user_id'])
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $userId)
            ->first();

        // Reuse an existing presentation so repeat visits keep attempt history.
        if (! $presentation) {
            $presentation = new Presentation;
            $presentation->user_id = $userId;
            $presentation->assessment_id = $assessment->id;
            $presentation->save();
            $created = true;
        }

        $attempts = Attempt::select(['id', 'answer_id', 'created_at'])
            ->with(['answer:id,correct'])
            ->where('presentation_id', $presentation->id)
            ->orderBy('created_at')
            ->get();

        $appeals = Appeal::select(['id', 'question_id', 'body'])
            ->where('presentation_id', $presentation->id)
            ->orderBy('created_at')
            ->get();

        $presentation->setRelation('assessment', $assessment);
        $presentation->setRelation('attempts', $attempts);
        $presentation->setRelation('appeals', $appeals);

        return [
            'presentation' => $presentation,
            'created' => $created,
        ];
    }
}
