<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expanded assessment payload for the editor UI (questions + presentations).
 *
 * @mixin \App\Models\Assessment
 */
class AssessmentEditResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'course' => $this->course,
            'memo' => $this->memo,
            'active' => (bool) $this->active,
            'short_url' => $this->short_url,
            'bitly_error' => $this->bitly_error,
            'password' => $this->password,
            'scheduled_at' => $this->scheduled_at,
            'time_limit' => $this->time_limit,
            'penalty_method' => $this->penalty_method,
            'questions' => QuestionEditResource::collection($this->questions ?? collect()),
            'presentations' => PresentationEditResource::collection($this->presentations ?? collect()),
        ];
    }
}
