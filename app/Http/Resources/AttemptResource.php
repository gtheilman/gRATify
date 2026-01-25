<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Attempt payload optionally including the related answer.
 *
 * @mixin \App\Models\Attempt
 */
class AttemptResource extends JsonResource
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
            'presentation_id' => $this->presentation_id,
            'answer_id' => $this->answer_id,
            'points' => $this->points,
            'answer' => $this->whenLoaded('answer', function () {
                return AnswerResource::make($this->answer);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
