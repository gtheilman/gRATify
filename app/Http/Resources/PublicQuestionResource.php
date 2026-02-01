<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public question payload with answers sorted for display.
 *
 * @mixin \App\Models\Question
 */
class PublicQuestionResource extends JsonResource
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
            'answers' => PublicAnswerResource::collection($this->answers ?? collect()),
        ];
    }
}
