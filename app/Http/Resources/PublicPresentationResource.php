<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public presentation payload (assessment + attempts) for the student client.
 *
 * @mixin \App\Models\Presentation
 */
class PublicPresentationResource extends JsonResource
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
            'assessment' => PublicAssessmentResource::make($this->assessment),
            'attempts' => PublicAttemptResource::collection($this->attempts ?? collect()),
        ];
    }
}
