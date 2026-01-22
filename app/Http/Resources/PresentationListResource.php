<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal presentation list item used by the completed presentations view.
 *
 * @mixin \App\Models\Presentation
 */
class PresentationListResource extends JsonResource
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
            'title' => $this->assessment?->title,
            'name' => $this->assessment?->user?->name,
            'created_at' => $this->created_at,
        ];
    }
}
