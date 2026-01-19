<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $answers = $this->answers ?? collect();
        $sortedAnswers = $answers->sortBy(fn ($answer) => $answer->sequence)->values();

        return [
            'id' => $this->id,
            'assessment_id' => $this->assessment_id,
            'stem' => $this->stem,
            'points_possible' => $this->points_possible,
            'sequence' => $this->sequence,
            'answers' => PublicAnswerResource::collection($sortedAnswers),
        ];
    }
}
