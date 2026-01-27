<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal appeal payload for student and scores views.
 *
 * @mixin \App\Models\Appeal
 */
class AppealResource extends JsonResource
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
            'question_id' => $this->question_id,
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
