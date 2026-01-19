<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $answerCorrect = $this->answer ? (bool) $this->answer->correct : false;

        return [
            'id' => $this->id,
            'presentation_id' => $this->presentation_id,
            'answer_id' => $this->answer_id,
            'points' => $this->points,
            'answer_correct' => $answerCorrect,
            'answer' => AnswerResource::make($this->answer),
        ];
    }
}
