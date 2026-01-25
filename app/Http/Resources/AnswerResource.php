<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full answer payload for editor/admin views (includes correctness + feedback).
 *
 * @mixin \App\Models\Answer
 */
class AnswerResource extends JsonResource
{
    public static $wrap = null;

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
            'feedback' => $this->feedback,
            'correct' => (bool) $this->correct,
            'sequence' => $this->sequence,
            'points' => $this->points,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
