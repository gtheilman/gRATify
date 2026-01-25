<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight answer payload for progress/feedback views.
 *
 * @mixin \App\Models\Answer
 */
class AnswerProgressResource extends JsonResource
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
            'question_id' => $this->question_id,
            'answer_text' => $this->answer_text,
            'correct' => (bool) $this->correct,
            'sequence' => $this->sequence,
        ];
    }
}
