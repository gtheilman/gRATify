<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Progress payload consumed by presenter/feedback views.
 *
 * @mixin \App\Models\Assessment
 */
class AssessmentProgressResource extends JsonResource
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
            'questions' => QuestionProgressResource::collection($this->questions ?? collect()),
            'presentations' => PresentationProgressResource::collection($this->presentations ?? collect()),
        ];
    }
}
