<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Scored presentation payload used by the scoring view.
 *
 * @mixin \App\Models\Presentation
 * @property float|int|null $score
 */
class ScoredPresentationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assessment_id' => $this->assessment_id,
            'user_id' => $this->user_id,
            'score' => $this->score,
            'assessment' => AssessmentScoreResource::make($this->assessment),
        ];
    }
}
