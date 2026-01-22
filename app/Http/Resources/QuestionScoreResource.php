<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Question score payload with ordered attempts for score breakdowns.
 *
 * @mixin \App\Models\Question
 * @property float|int|null $score
 */
class QuestionScoreResource extends JsonResource
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
            'stem' => $this->stem,
            'sequence' => $this->sequence,
            'score' => $this->score ?? 0,
            'attempts' => AttemptResource::collection($this->attempts ?? collect()),
        ];
    }
}
