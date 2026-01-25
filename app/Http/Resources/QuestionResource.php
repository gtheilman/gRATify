<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Question payload used by editor CRUD endpoints.
 *
 * @mixin \App\Models\Question
 */
class QuestionResource extends JsonResource
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
            'answers' => $this->whenLoaded('answers', function () {
                return AnswerResource::collection($this->answers);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
