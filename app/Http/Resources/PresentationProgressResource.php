<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Presentation payload for progress/feedback views (includes group label + attempts).
 *
 * @mixin \App\Models\Presentation
 */
class PresentationProgressResource extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'user_id' => $this->user_id,
            'group_label' => $this->group_label ?? $this->user_id,
            'attempts' => AttemptResource::collection($this->attempts ?? collect()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
