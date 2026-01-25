<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight question payload for progress/feedback views.
 *
 * @mixin \App\Models\Question
 */
class QuestionProgressResource extends JsonResource
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
            'stem' => $this->stem,
            'sequence' => $this->sequence,
            'answers' => AnswerProgressResource::collection($this->answers ?? collect()),
        ];
    }
}
