<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight question payload for assessment editor views.
 *
 * @mixin \App\Models\Question
 */
class QuestionEditResource extends JsonResource
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
            'title' => $this->title,
            'stem' => $this->stem,
            'sequence' => $this->sequence,
            'answers' => AnswerEditResource::collection($this->answers ?? collect()),
        ];
    }
}
