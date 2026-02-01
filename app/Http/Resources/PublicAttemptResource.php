<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public attempt payload exposes correctness summary without leaking full answer keys.
 *
 * @mixin \App\Models\Attempt
 */
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
            'answer_id' => $this->answer_id,
            'answer_correct' => $answerCorrect,
            'answer' => $this->answer ? [
                'id' => $this->answer->id,
                'correct' => $answerCorrect,
            ] : null,
        ];
    }
}
