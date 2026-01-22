<?php

namespace App\Services\Presentations;

use App\Models\Assessment;
use App\Models\Attempt;
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
        $presentation = Presentation::where('assessment_id', $assessment->id)
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

        $attempts = Attempt::with('answer')
            ->where('presentation_id', $presentation->id)
            ->get();

        $presentation->setRelation('assessment', $assessment);
        $presentation->setRelation('attempts', $attempts);

        return [
            'presentation' => $presentation,
            'created' => $created,
        ];
    }
}
