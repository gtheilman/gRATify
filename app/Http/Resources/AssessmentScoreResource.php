<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Assessment payload optimized for scoring views (questions + per-question scores).
 *
 * @mixin \App\Models\Assessment
 */
class AssessmentScoreResource extends JsonResource
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
            'title' => $this->title,
            'active' => (bool) $this->active,
            'appeals_open' => (bool) $this->appeals_open,
            'questions' => QuestionScoreResource::collection($this->questions ?? collect()),
        ];
    }
}
