<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detailed question payload for editor/progress views.
 *
 * @mixin \App\Models\Question
 */
class QuestionDetailResource extends JsonResource
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
            'points_possible' => $this->points_possible,
            'sequence' => $this->sequence,
            'attempts' => AttemptResource::collection($this->attempts ?? collect()),
            'answers' => AnswerResource::collection($this->answers ?? collect()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
