<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public assessment payload for the student client (no correctness exposed).
 *
 * @mixin \App\Models\Assessment
 */
class PublicAssessmentResource extends JsonResource
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
            'title' => $this->title,
            'course' => $this->course,
            'active' => (bool) $this->active,
            'short_url' => $this->short_url,
            'bitly_error' => $this->bitly_error,
            'questions' => PublicQuestionResource::collection($this->questions ?? collect()),
        ];
    }
}
